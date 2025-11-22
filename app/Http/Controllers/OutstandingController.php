<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class OutstandingController extends Controller
{
    /**
     * Display a listing of item outstanding requests.
     */
    public function index()
    {
        $requests = Session::get('warehouse_requests', []);
        $poItems = Session::get('data_po_items', []);

        // Ensure all requests have maximal_stock field (for backward compatibility)
        foreach ($requests as &$req) {
            if (!isset($req['maximal_stock'])) {
                $req['maximal_stock'] = 0;
            }
        }
        unset($req);

        $requests = array_filter($requests, function ($req) {
            if (empty($req['request_date'])) {
                return false;
            }
            try {
                $year = (int) date('Y', strtotime($req['request_date']));
                return $year >= 2000;
            } catch (\Exception $e) {
                return false;
            }
        });

        $requests = array_values($requests);

        usort($requests, function ($a, $b) {
            return strtotime($b['request_date']) - strtotime($a['request_date']);
        });

        // Process PO data for each request
        foreach ($requests as &$req) {
            $itemCode = strtolower(trim($req['item_code'] ?? ''));
            $itemName = strtolower(trim($req['item_name'] ?? ''));
            
            // Find matching PO items
            $matchingPOs = [];
            foreach ($poItems as $poItem) {
                $poItemCode = strtolower(trim($poItem['item_code'] ?? ''));
                $poItemName = strtolower(trim($poItem['item_name'] ?? ''));
                
                if ($itemCode === $poItemCode && $itemName === $poItemName) {
                    $matchingPOs[] = $poItem;
                }
            }
            
            // Group by PO NO and sum qty
            $poGroups = [];
            foreach ($matchingPOs as $po) {
                $poNo = trim($po['po_no'] ?? '');
                if (empty($poNo)) {
                    $poNo = '-'; // Handle empty PO NO
                }
                
                if (!isset($poGroups[$poNo])) {
                    $poGroups[$poNo] = [
                        'po_no' => $poNo,
                        'total_qty' => 0,
                        'supplier_name' => $po['supplier_name'] ?? '-',
                        'items' => []
                    ];
                }
                
                $poGroups[$poNo]['total_qty'] += (int)($po['scheduled_receipt_qty'] ?? 0);
                $poGroups[$poNo]['items'][] = $po;
            }
            
            // Store PO data in request
            $req['po_data'] = array_values($poGroups);
            $req['total_receipt_qty'] = array_sum(array_column($poGroups, 'total_qty'));
            $req['has_multiple_po'] = count($poGroups) > 1;
        }
        unset($req);

        Session::put('warehouse_requests', $requests);

        return view('pages.item_outstanding', compact('requests'));
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'user' => 'nullable|string|max:255',
            'outstanding' => 'nullable|integer|min:0',
            'sudah_pp' => 'nullable|integer|min:0',
            'outstanding_pp' => 'nullable|string|max:255',
            'ending_balance' => 'nullable|integer|min:0',
            'maximal_stock' => 'nullable|integer|min:0',
            'order_point' => 'nullable|integer|min:0',
            'minimal_stock' => 'nullable|integer|min:0',
        ]);

        $requests = Session::get('warehouse_requests', []);
        $today = now()->format('Y-m-d');

        $itemKey = strtolower(trim($validated['item_code']) . '|' . trim($validated['item_name']));
        $isDuplicate = false;
        foreach ($requests as $req) {
            $existingKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
            if ($itemKey === $existingKey) {
                $isDuplicate = true;
                break;
            }
        }

        $newRequest = [
            'id' => uniqid(),
            'request_date' => $today,
            'item_code' => $validated['item_code'] ?? '',
            'item_name' => $validated['item_name'] ?? '',
            'user' => $validated['user'] ?? '',
            'outstanding' => $validated['outstanding'] ?? 0,
            'sudah_pp' => $validated['sudah_pp'] ?? 0,
            'outstanding_pp' => $validated['outstanding_pp'] ?? '',
            'ending_balance' => $validated['ending_balance'] ?? 0,
            'maximal_stock' => $validated['maximal_stock'] ?? 0,
            'order_point' => $validated['order_point'] ?? 0,
            'minimal_stock' => $validated['minimal_stock'] ?? 0,
            'note' => null,
            'imported_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
            'duplicate_note' => $isDuplicate ? 'Item ini sudah ada di list' : null,
        ];

        array_unshift($requests, $newRequest);
        Session::put('warehouse_requests', $requests);

        $message = $isDuplicate
            ? 'Request ditambahkan dengan note: Item ini sudah ada di list'
            : 'Request berhasil dibuat!';

        return redirect()->route('item_outstanding.index')->with('success', $message);
    }

    /**
     * Import Excel file and process the data
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            array_shift($rows);

            $requests = Session::get('warehouse_requests', []);
            $today = now()->format('Y-m-d');
            $imported = 0;
            $updated = 0;

            // Build map of existing items in warehouse_requests
            $existingItems = [];
            foreach ($requests as $req) {
                $itemKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    $existingItems[$itemKey] = true;
                }
            }

            // Build map of total outstanding from item outstanding list
            $totalOutstandingMap = [];
            
            // Count outstanding from warehouse_requests
            foreach ($requests as $req) {
                $itemKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    $totalOutstandingMap[$itemKey] = ($totalOutstandingMap[$itemKey] ?? 0) + (int) ($req['outstanding'] ?? 0);
                }
            }
            

            foreach ($rows as $row) {
                if (empty(array_filter($row))) {
                    continue;
                }

                // Excel structure:
                // Column A (0): Item Code
                // Column B (1): Description
                // Column C (2): OUTSTANDING
                // Column D (3): ENDING BALANCE
                // Column E (4): MAX
                // Column F (5): ORDER POINT
                // Column G (6): MIN
                // Column H (7): USER
                // Column I (8): Outstanding PP
                $itemCode = trim($row[0] ?? '');
                $itemName = trim($row[1] ?? '');
                $excelOutstanding = (int) ($row[2] ?? 0);
                $endingBalance = $row[3] ?? 0;
                $maximalStock = $row[4] ?? 0;
                $orderPoint = $row[5] ?? 0;
                $minimalStock = $row[6] ?? 0;
                $user = trim($row[7] ?? '');
                $outstandingPp = trim($row[8] ?? '');

                if (empty($itemCode) || empty($itemName)) {
                    continue;
                }

                $requestDate = $today;
                $itemKey = strtolower($itemCode . '|' . $itemName);
                
                // Get current total outstanding from all pages
                $currentTotalOutstanding = $totalOutstandingMap[$itemKey] ?? 0;
                
                // Calculate difference: excel outstanding - current total outstanding
                $outstandingDifference = $excelOutstanding - $currentTotalOutstanding;

                if (isset($existingItems[$itemKey])) {
                    // Item exists in warehouse_requests, update it
                    foreach ($requests as &$req) {
                        $existingKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
                        if ($existingKey === $itemKey) {
                            // Get current outstanding in warehouse_requests
                            $currentWarehouseOutstanding = (int) ($req['outstanding'] ?? 0);
                            
                            // Calculate new outstanding: current + difference
                            $newOutstanding = $currentWarehouseOutstanding + $outstandingDifference;
                            
                            // Ensure outstanding is not negative
                            if ($newOutstanding < 0) {
                                $newOutstanding = 0;
                            }
                            
                            $req['outstanding'] = $newOutstanding;
                            $req['outstanding_pp'] = $outstandingPp;
                            $req['ending_balance'] = (int) ($endingBalance ?: 0);
                            $req['maximal_stock'] = (int) ($maximalStock ?: 0);
                            $req['order_point'] = (int) ($orderPoint ?: 0);
                            $req['minimal_stock'] = (int) ($minimalStock ?: 0);
                            $req['user'] = $user;
                            $req['imported_at'] = now()->toDateTimeString();
                            break;
                        }
                    }
                    unset($req);
                    $updated++;
                } else {
                    // Item doesn't exist in warehouse_requests, add with full outstanding from excel
                    $newOutstanding = $excelOutstanding;
                    
                    $newRequest = [
                        'id' => uniqid(),
                        'request_date' => $requestDate,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'user' => $user,
                        'outstanding' => $newOutstanding,
                        'outstanding_pp' => $outstandingPp,
                        'ending_balance' => (int) ($endingBalance ?: 0),
                        'maximal_stock' => (int) ($maximalStock ?: 0),
                        'order_point' => (int) ($orderPoint ?: 0),
                        'minimal_stock' => (int) ($minimalStock ?: 0),
                        'note' => null,
                        'imported_at' => now()->toDateTimeString(),
                        'duplicate_note' => null,
                    ];

                    array_unshift($requests, $newRequest);
                    $existingItems[$itemKey] = true;
                    $imported++;
                }
            }

            // Remove items with outstanding = 0
            $requests = array_filter($requests, function ($req) {
                return ($req['outstanding'] ?? 0) > 0;
            });
            $requests = array_values($requests);

            Session::put('warehouse_requests', $requests);

            $message = "Import berhasil! {$imported} item baru ditambahkan.";
            if ($updated > 0) {
                $message .= " {$updated} item duplicate diperbarui (outstanding, outstanding pp).";
            }

            return redirect()->route('item_outstanding.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('item_outstanding.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Update note for a request.
     */
    public function updateNote(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $requests = Session::get('warehouse_requests', []);
        foreach ($requests as &$req) {
            if (($req['id'] ?? '') === $id) {
                $req['note'] = $validated['note'] ?? null;
                break;
            }
        }
        unset($req);

        Session::put('warehouse_requests', $requests);

        return response()->json([
            'success' => true,
            'message' => 'Note berhasil diperbarui',
        ]);
    }

    /**
     * Update sudah follow for a request.
     */
    public function updateFollow(Request $request, $id)
    {
        $validated = $request->validate([
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $requests = Session::get('warehouse_requests', []);
        foreach ($requests as &$req) {
            if (($req['id'] ?? '') === $id) {
                $req['sudah_follow'] = $validated['sudah_follow'] ?? '';
                // Save timestamp for last edited
                $req['sudah_follow_edited_at'] = Carbon::now('Asia/Jakarta')->toDateTimeString();
                break;
            }
        }
        unset($req);

        Session::put('warehouse_requests', $requests);

        $editedAt = Carbon::now('Asia/Jakarta');
        $formattedDate = strtolower($editedAt->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'SUDAH FOLLOW berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update pengiriman tanggal for a request.
     */
    public function updatePengirimanTanggal(Request $request, $id)
    {
        $validated = $request->validate([
            'pengiriman_tanggal' => 'nullable|date',
        ]);

        $requests = Session::get('warehouse_requests', []);
        foreach ($requests as &$req) {
            if (($req['id'] ?? '') === $id) {
                $req['pengiriman_tanggal'] = $validated['pengiriman_tanggal'] ?? null;
                // Save timestamp for last edited
                $req['pengiriman_tanggal_edited_at'] = Carbon::now('Asia/Jakarta')->toDateTimeString();
                break;
            }
        }
        unset($req);

        Session::put('warehouse_requests', $requests);

        $editedAt = Carbon::now('Asia/Jakarta');
        $formattedDate = strtolower($editedAt->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'PENGIRIMAN TANGGAL berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }
}



