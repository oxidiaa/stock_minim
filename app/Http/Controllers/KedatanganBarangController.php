<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KedatanganBarangController extends Controller
{
    /**
     * Display a listing of arrival items.
     */
    public function index()
    {
        $importSummary = Session::get('kedatangan_import_summary', ['items' => [], 'item_count' => 0]);

        return view('pages.kedatangan_barang', [
            'importSummary' => $importSummary,
        ]);
    }

    /**
     * Import Excel file, update outstanding totals, and move arrived items to history.
     */
    public function importExcel(Request $request)
    {
        $validated = $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
            'arrival_date' => 'required|date',
        ]);

        try {
            $file = $request->file('excel_file');
            $arrivalDate = $validated['arrival_date'];

            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            if (!empty($rows)) {
                array_shift($rows);
            }

            $masterItems = Session::get('data_master_items', []);
            $warehouseRequests = Session::get('warehouse_requests', []);
            $historyItems = Session::get('history_items', []);

            // Build maps for quick lookup
            $masterItemsMap = [];
            foreach ($masterItems as $index => $item) {
                $itemKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    $masterItemsMap[$itemKey] = $index;
                }
            }

            $warehouseRequestsMap = [];
            foreach ($warehouseRequests as $index => $item) {
                $itemKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    if (!isset($warehouseRequestsMap[$itemKey])) {
                        $warehouseRequestsMap[$itemKey] = [];
                    }
                    $warehouseRequestsMap[$itemKey][] = $index;
                }
            }

            $updatedItems = 0;
            $movedToHistoryCount = 0;
            $historyDetails = [];
            $summaryItems = [];

            foreach ($rows as $row) {
                if (empty(array_filter($row))) {
                    continue;
                }

                // Expected Excel structure:
                // Column A (0): Item Code
                // Column B (1): Description
                // Column C (2): Quantity Arrived (Outstanding to deduct)
                $itemCode = trim($row[0] ?? '');
                $itemName = trim($row[1] ?? '');
                $arrivalQty = (int) ($row[2] ?? 0);

                if (empty($itemCode) || empty($itemName) || $arrivalQty <= 0) {
                    continue;
                }

                $itemKey = strtolower($itemCode . '|' . $itemName);

                // Check if item exists in master data or outstanding list
                $existsInMaster = isset($masterItemsMap[$itemKey]);
                $existsInWarehouse = isset($warehouseRequestsMap[$itemKey]);

                if (!$existsInMaster && !$existsInWarehouse) {
                    continue; // Item not found, skip
                }

                $totalDeducted = 0;

                if ($existsInMaster) {
                    $masterIndex = $masterItemsMap[$itemKey];
                    $currentMasterOutstanding = (int) ($masterItems[$masterIndex]['outstanding'] ?? 0);

                    if ($currentMasterOutstanding > 0 && $arrivalQty > 0) {
                        $masterItems[$masterIndex]['outstanding'] = max(0, $currentMasterOutstanding - $arrivalQty);
                        $updatedItems++;
                    }

                    if ($arrivalQty > 0) {
                        $totalDeducted = $arrivalQty;
                    }

                    $targetWarehouseOutstanding = (int) ($masterItems[$masterIndex]['outstanding'] ?? 0);

                    if ($existsInWarehouse) {
                        if (count($warehouseRequestsMap[$itemKey]) > 0) {
                            $firstIndex = $warehouseRequestsMap[$itemKey][0];
                            $warehouseRequests[$firstIndex]['outstanding'] = $targetWarehouseOutstanding;

                            for ($i = 1; $i < count($warehouseRequestsMap[$itemKey]); $i++) {
                                $otherIndex = $warehouseRequestsMap[$itemKey][$i];
                                $warehouseRequests[$otherIndex]['outstanding'] = 0;
                            }
                            $updatedItems++;
                        }
                    } else {
                        if ($targetWarehouseOutstanding > 0) {
                            $masterItem = $masterItems[$masterIndex];
                            $newRequest = [
                                'id' => uniqid(),
                                'request_date' => now()->format('Y-m-d'),
                                'item_code' => $itemCode,
                                'item_name' => $itemName,
                                'user' => $masterItem['user'] ?? '',
                                'outstanding' => $targetWarehouseOutstanding,
                                'outstanding_pp' => $masterItem['outstanding_pp'] ?? '',
                                'ending_balance' => (int) ($masterItem['ending_balance'] ?? 0),
                                'order_point' => (int) ($masterItem['order_point'] ?? 0),
                                'minimal_stock' => (int) ($masterItem['minimal_stock'] ?? 0),
                                'note' => null,
                                'imported_at' => now()->toDateTimeString(),
                                'duplicate_note' => null,
                            ];
                            array_unshift($warehouseRequests, $newRequest);
                            $updatedItems++;
                        }
                    }
                } else {
                    // Item only exists in warehouse requests
                    if ($existsInWarehouse) {
                        $remainingArrival = $arrivalQty;
                        foreach ($warehouseRequestsMap[$itemKey] as $index) {
                            if ($remainingArrival <= 0) {
                                break;
                            }
                            $currentOutstanding = (int) ($warehouseRequests[$index]['outstanding'] ?? 0);
                            if ($currentOutstanding <= 0) {
                                continue;
                            }
                            $deductAmount = min($remainingArrival, $currentOutstanding);
                            $warehouseRequests[$index]['outstanding'] = $currentOutstanding - $deductAmount;
                            if ($deductAmount > 0) {
                                $updatedItems++;
                                $totalDeducted += $deductAmount;
                                $remainingArrival -= $deductAmount;
                            }
                        }
                    }
                }

                if ($totalDeducted > 0) {
                    // Record to history
                    $historyEntry = [
                        'id' => uniqid(),
                        'arrival_date' => $arrivalDate,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'jumlah_item_datang' => $totalDeducted,
                    ];
                    $historyItems[] = $historyEntry;
                    $movedToHistoryCount++;
                    $historyDetails[] = "{$itemCode} - {$itemName}: " . number_format($totalDeducted, 0, ',', '.');

                    $summaryItems[] = [
                        'history_id' => $historyEntry['id'],
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'arrived_qty' => $totalDeducted,
                        'arrival_date' => $arrivalDate,
                    ];
                }
            }

            // Remove items with outstanding = 0
            $warehouseRequests = array_filter($warehouseRequests, function ($item) {
                return ($item['outstanding'] ?? 0) > 0;
            });
            $warehouseRequests = array_values($warehouseRequests);

            Session::put('data_master_items', $masterItems);
            Session::put('warehouse_requests', $warehouseRequests);
            Session::put('history_items', $historyItems);
            
            if (!empty($summaryItems)) {
                Session::put('kedatangan_import_summary', [
                    'arrival_date' => $arrivalDate,
                    'item_count' => count($summaryItems),
                    'items' => $summaryItems,
                ]);
            }

            if ($updatedItems === 0) {
                return redirect()->route('kedatangan_barang.index')
                    ->with('error', 'Tidak ada item outstanding yang cocok dengan data Excel.');
            }

            $message = "Import berhasil! Outstanding dari semua halaman diperbarui.";
            if ($movedToHistoryCount > 0) {
                $message .= " {$movedToHistoryCount} item dipindahkan ke History (tanggal kedatangan " . date('d/m/Y', strtotime($arrivalDate)) . ").";
            }
            if (!empty($historyDetails)) {
                $message .= " Detail: " . implode('; ', $historyDetails) . ".";
            }

            return redirect()->route('kedatangan_barang.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('kedatangan_barang.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }
}


