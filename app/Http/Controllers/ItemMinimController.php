<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\ItemMaster;
use App\Models\DataPO;
use App\Models\FollowUpPO;
use Carbon\Carbon;

class ItemMinimController extends Controller
{
    /**
     * Display a listing of items where ending_balance <= order_point AND outstanding > 0.
     */
    public function index()
    {
        // Eloquent: Get items where ending_balance <= order_point AND outstanding > 0
        // We use whereColumn for comparing two columns
        $minimItems = ItemMaster::whereColumn('ending_balance', '<=', 'order_point')
                                ->where('outstanding', '>', 0)
                                ->orderBy('item_code')
                                ->get();
        
        // Fetch POs for these items
        if ($minimItems->isNotEmpty()) {
            $itemCodes = $minimItems->pluck('item_code')->unique()->toArray();
            
            // Optimization: Fetch only relevant POs
            $poItems = DataPO::whereIn('item_code', $itemCodes)->get();
            // Fetch follow ups per item
            $followUps = FollowUpPO::whereIn('item_master_id', $minimItems->pluck('id'))->get()->groupBy('item_master_id');
            
            // Check for duplicate PO numbers across entire data_po table
            $duplicatePoNos = DataPO::select('po_no', DB::raw('COUNT(*) as count'))
                ->whereNotNull('po_no')
                ->where('po_no', '!=', '')
                ->where('po_no', '!=', '-')
                ->groupBy('po_no')
                ->having('count', '>', 1)
                ->pluck('po_no')
                ->toArray();
            
            // Group POs for mapping
            $poLookup = [];
            foreach ($poItems as $po) {
                // Use strict item_code matching only for reliability
                $key = strtolower(trim($po->item_code));
                if (!isset($poLookup[$key])) {
                    $poLookup[$key] = [];
                }
                $poLookup[$key][] = $po;
            }
            
            // Attach PO data to items
            foreach ($minimItems as $item) {
                $key = strtolower(trim($item->item_code));
                $matchingPOs = $poLookup[$key] ?? [];
                
                $poGroups = [];
                foreach ($matchingPOs as $po) {
                    $poNo = trim($po->po_no);
                    if (empty($poNo)) $poNo = '-';
                    
                    if (!isset($poGroups[$poNo])) {
                        $poGroups[$poNo] = [
                            'po_no' => $poNo,
                            'total_qty' => 0,
                            'supplier_name' => $po->supplier_name ?? '-',
                            'items' => []
                        ];
                    }
                    $poGroups[$poNo]['total_qty'] += (int)$po->scheduled_receipt_qty;
                    $poGroups[$poNo]['items'][] = $po;
                }
                
                // Attach follow up info
                $itemFollowUps = $followUps[$item->id] ?? collect();
                $followUpMap = $itemFollowUps->keyBy(function ($f) {
                    return trim($f->po_no) === '' ? '-' : trim($f->po_no);
                });
                $totalFollowedQty = 0;

                foreach ($poGroups as &$group) {
                    $poNoKey = $group['po_no'];
                    $group['followed'] = false; // Default: Not followed / NO
                    $group['followed_status'] = 'NO';
                    $group['followed_qty'] = 0;
                    $group['followed_pengiriman_tanggal'] = null;

                    if ($followUpMap->has($poNoKey)) {
                        $fu = $followUpMap->get($poNoKey);
                        // If record exists, we check the explicit status
                        $group['followed_status'] = $fu->sudah_follow ?? 'NO';
                        $group['followed'] = ($group['followed_status'] === 'YES');
                        $group['followed_qty'] = (int) ($fu->qty_akan_dikirim ?? 0);
                        // pengiriman_tanggal is already a Carbon instance due to model cast
                        $group['followed_pengiriman_tanggal'] = $fu->pengiriman_tanggal
                            ? $fu->pengiriman_tanggal->format('Y-m-d')
                            : null;
                        $group['followed_edited_at'] = $fu->updated_at;
                        
                        $totalFollowedQty += $group['followed_qty'];
                    } else {
                        $group['followed_edited_at'] = null;
                    }
                }
                unset($group);

                // Check if any PO number for this item is duplicate
                $hasDuplicatePo = false;
                $duplicatePoNo = null;
                
                // Check if this item has a PO number that is duplicate
                foreach ($poGroups as $poNo => $poGroup) {
                    if (in_array($poNo, $duplicatePoNos)) {
                        $hasDuplicatePo = true;
                        $duplicatePoNo = $poNo;
                        break; // Use the first duplicate PO found
                    }
                }
                
                // If has duplicate PO, fetch all records with that PO number
                if ($hasDuplicatePo && $duplicatePoNo) {
                    // Fetch all PO records with this PO number from entire table
                    // HANYA PO record untuk item ini saja
                    $allPoRecords = DataPO::where('po_no', $duplicatePoNo)
                    ->where('item_code', $item->item_code)
                    ->get();

                    
                    // Group by item_code to show different items with same PO
                    $duplicatePoGroups = [];
                    $currentItemKey = strtolower(trim($item->item_code));
                    
                    foreach ($allPoRecords as $poRecord) {
                        $itemKey = strtolower(trim($poRecord->item_code));
                        if (!isset($duplicatePoGroups[$itemKey])) {
                            $duplicatePoGroups[$itemKey] = [
                                'po_no' => $duplicatePoNo,
                                'item_code' => $poRecord->item_code,
                                'item_name' => $poRecord->item_name,
                                'total_qty' => 0,
                                'supplier_name' => $poRecord->supplier_name ?? '-',
                                'items' => [],
                                // Initialize follow-up keys
                                'followed' => false,
                                'followed_status' => 'NO',
                                'followed_qty' => 0,
                                'followed_pengiriman_tanggal' => null,
                                'followed_edited_at' => null,
                            ];
                        }
                        $duplicatePoGroups[$itemKey]['total_qty'] += (int)$poRecord->scheduled_receipt_qty;
                        $duplicatePoGroups[$itemKey]['items'][] = $poRecord;
                    }
                    
                    // Attach follow-up info to duplicate PO groups
                    foreach ($duplicatePoGroups as $itemKey => &$group) {
                        $poNoKey = $group['po_no'];
                        if ($followUpMap->has($poNoKey)) {
                            $fu = $followUpMap->get($poNoKey);
                            // Only attach follow-up if it's for the current item
                            $fuItemMasterId = $fu->item_master_id;
                            if ($fuItemMasterId == $item->id) {
                                $group['followed_status'] = $fu->sudah_follow ?? 'NO';
                                $group['followed'] = ($group['followed_status'] === 'YES');
                                $group['followed_qty'] = (int) ($fu->qty_akan_dikirim ?? 0);
                                // pengiriman_tanggal is already a Carbon instance due to model cast
                                $group['followed_pengiriman_tanggal'] = $fu->pengiriman_tanggal
                                    ? $fu->pengiriman_tanggal->format('Y-m-d')
                                    : null;
                                $group['followed_edited_at'] = $fu->updated_at;
                            }
                        }
                    }
                    unset($group);
                    
                    // Sort so current item appears first
                    $sortedGroups = [];
                    if (isset($duplicatePoGroups[$currentItemKey])) {
                        $sortedGroups[] = $duplicatePoGroups[$currentItemKey];
                    }
                    foreach ($duplicatePoGroups as $itemKey => $group) {
                        if ($itemKey !== $currentItemKey) {
                            $sortedGroups[] = $group;
                        }
                    }
                    
                    $item->po_data = array_values($poGroups);
                    $item->has_multiple_po = count($poGroups) > 1;

                } else {
                    // gunakan hasil grouping PO per item
                    $item->po_data = array_values($poGroups);
                    $item->has_multiple_po = count($poGroups) > 1;
                }
                
                
                // Determine Active PO Group to Display Initially
                $activePoGroup = null;
                if (!empty($poGroups)) {
                    $selectedPo = trim($item->selected_po_no ?? '');
                    if ($selectedPo && isset($poGroups[$selectedPo])) {
                        $activePoGroup = $poGroups[$selectedPo];
                    } else {
                        // Default to first
                        $activePoGroup = reset($poGroups);
                    }
                }

                // Overwrite item properties for display purposes
                if ($activePoGroup) {
                    $item->sudah_follow = $activePoGroup['followed_status'];
                    $item->qty_akan_dikirim = $activePoGroup['followed_qty'];
                    $item->pengiriman_tanggal = $activePoGroup['followed_pengiriman_tanggal'];
                    // Use PO specific updated_at for the display, fallback to null if no follow up exists
                    $item->sudah_follow_edited_at = $activePoGroup['followed_edited_at'] ?? null;
                    // For pengiriman tanggal edited, we also use the same updated_at since they are in the same record
                    $item->pengiriman_tanggal_edited_at = ($activePoGroup['followed_pengiriman_tanggal'] ?? null) ? ($activePoGroup['followed_edited_at'] ?? null) : null;
                } else {
                    $item->sudah_follow = 'NO';
                    $item->qty_akan_dikirim = 0;
                    $item->pengiriman_tanggal = null;
                    $item->sudah_follow_edited_at = null;
                    $item->pengiriman_tanggal_edited_at = null;
                }

                $item->total_receipt_qty = array_sum(array_column($poGroups, 'total_qty'));
                $item->total_followed_qty = $totalFollowedQty;
            }
        }

        return view('pages.item_minim', compact('minimItems'));
    }

    /**
     * Update note for a minim item.
     */
    public function updateNote(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $item = ItemMaster::find($id);
        if ($item) {
            $item->note = $validated['note'] ?? null;
            $item->save();
            return response()->json(['success' => true, 'message' => 'Note berhasil diperbarui']);
        }

        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    /**
     * Update a minim item.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'outstanding' => 'required|integer|min:0',
            'ending_balance' => 'required|integer|min:0',
            'maximal_stock' => 'required|integer|min:0',
            'order_point' => 'required|integer|min:0',
            'minimal_stock' => 'required|integer|min:0',
            'user' => 'nullable|string|max:255',
            'outstanding_pp' => 'nullable|string|max:255',
        ]);

        $item = ItemMaster::find($id);
        if (!$item) {
             return redirect()->route('item_minim.index')->with('error', 'Item tidak ditemukan.');
        }

        $item->update($validated); // Mass assign

        return redirect()->route('item_minim.index')->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Delete a minim item.
     */
    public function destroy($id)
    {
        $item = ItemMaster::find($id);
        if (!$item) {
             return redirect()->route('item_minim.index')->with('error', 'Item tidak ditemukan.');
        }
        
        $item->delete();

        return redirect()->route('item_minim.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Update follow up for a minim item.
     */
    public function updateFollowUp(Request $request, $id)
    {
        // Check if user is purchasing or master
        if (!auth()->check() || (auth()->user()->username !== 'purchasing' && auth()->user()->username !== 'master')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya user purchasing atau master yang dapat mengakses fitur ini.'
            ], 403);
        }

        $validated = $request->validate([
            'qty_akan_dikirim' => 'nullable|integer|min:0',
            'pengiriman_tanggal' => 'nullable|date',
            'selected_po_no' => 'nullable|string|max:255',
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $item = ItemMaster::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
        }

        $selectedPo = $validated['selected_po_no'] ?? '';
        if (empty($selectedPo)) {
            return response()->json(['success' => false, 'message' => 'Silakan pilih NO PO terlebih dahulu'], 422);
        }

        // Simpan atau update follow up per PO
        $followUp = FollowUpPO::updateOrCreate(
            [
                'item_master_id' => $item->id,
                'po_no' => $selectedPo
            ],
            [
                'qty_akan_dikirim' => $validated['qty_akan_dikirim'] ?? null,
                'pengiriman_tanggal' => $validated['pengiriman_tanggal'] ?? null,
                'sudah_follow' => $validated['sudah_follow'] ?? 'NO',
            ]
        );

        // Update aggregate di ItemMaster: total qty dari semua follow up
        $totalFollowed = FollowUpPO::where('item_master_id', $item->id)->sum('qty_akan_dikirim');
        $item->qty_akan_dikirim = $totalFollowed;
        $item->sudah_follow = 'YES';
        $item->sudah_follow_edited_at = now();
        $item->pengiriman_tanggal = $validated['pengiriman_tanggal'] ?? null;
        $item->pengiriman_tanggal_edited_at = $validated['pengiriman_tanggal'] ? now() : null;
        $item->selected_po_no = $selectedPo;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Follow up berhasil diperbarui',
        ]);
    }
}
