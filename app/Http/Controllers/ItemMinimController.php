<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\ItemMaster;
use App\Models\DataPO;

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
                    $allPoRecords = DataPO::where('po_no', $duplicatePoNo)->get();
                    
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
                                'items' => []
                            ];
                        }
                        $duplicatePoGroups[$itemKey]['total_qty'] += (int)$poRecord->scheduled_receipt_qty;
                        $duplicatePoGroups[$itemKey]['items'][] = $poRecord;
                    }
                    
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
                    
                    $item->po_data = $sortedGroups;
                    $item->has_multiple_po = true; // Force show dropdown for duplicate PO
                } else {
                    // We attach these as dynamic properties which Blade can access via -> or []
                    $item->po_data = array_values($poGroups);
                    $item->has_multiple_po = count($poGroups) > 1;
                }
                
                $item->total_receipt_qty = array_sum(array_column($poGroups, 'total_qty'));
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
        // Check if user is purchasing
        if (!auth()->check() || auth()->user()->username !== 'purchasing') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya user purchasing yang dapat mengakses fitur ini.'
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

        $item->qty_akan_dikirim = $validated['qty_akan_dikirim'] ?? null;
        $item->pengiriman_tanggal = $validated['pengiriman_tanggal'] ?? null;
        $item->selected_po_no = $validated['selected_po_no'] ?? null;
        $item->sudah_follow = $validated['sudah_follow'] ?? 'YES';
        $item->sudah_follow_edited_at = now();
        $item->pengiriman_tanggal_edited_at = $validated['pengiriman_tanggal'] ? now() : null;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Follow up berhasil diperbarui',
        ]);
    }
}
