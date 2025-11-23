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
        $kedatanganItems = Session::get('kedatangan_barang_items', []);

        return view('pages.kedatangan_barang', [
            'importSummary' => $importSummary,
            'kedatanganItems' => $kedatanganItems,
        ]);
    }

    /**
     * Helper method to get cell value safely
     */
    private function getCellValue($value)
    {
        if ($value === null) {
            return '';
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        return trim((string)$value);
    }

    /**
     * Helper method to parse numeric value
     */
    private function parseNumericValue($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        // Remove non-numeric characters except decimal point and minus sign
        $cleaned = preg_replace('/[^0-9.-]/', '', (string)$value);
        return $cleaned !== '' ? (float)$cleaned : 0;
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

            if (empty($rows)) {
                return redirect()->route('kedatangan_barang.index')
                    ->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Find header row and column indices (same as Data PO)
            $headerRow = null;
            $columnIndices = [
                'item_code' => null,
                'item_name' => null,
                'supplier_name' => null,
                'scheduled_receipt_qty' => null,
                'po_no' => null,
            ];

            // Try to find header row (check first 5 rows)
            $maxHeaderSearch = min(5, count($rows));
            for ($i = 0; $i < $maxHeaderSearch; $i++) {
                $row = $rows[$i] ?? [];
                
                foreach ($row as $colIndex => $cellValue) {
                    if (empty($cellValue)) {
                        continue;
                    }
                    
                    $cellLower = strtolower(trim((string)$cellValue));
                    
                    if (preg_match('/item\s*cd|item\s*code|kode\s*item|code/i', $cellLower) && $columnIndices['item_code'] === null) {
                        $columnIndices['item_code'] = $colIndex;
                    }
                    if (preg_match('/item\s*name|nama\s*item|name/i', $cellLower) && $columnIndices['item_name'] === null) {
                        $columnIndices['item_name'] = $colIndex;
                    }
                    if (preg_match('/supplier\s*name|nama\s*supplier|supplier/i', $cellLower) && $columnIndices['supplier_name'] === null) {
                        $columnIndices['supplier_name'] = $colIndex;
                    }
                    if (preg_match('/sched\.?\s*receipt\s*qty|scheduled\s*receipt|receipt\s*qty|qty/i', $cellLower) && $columnIndices['scheduled_receipt_qty'] === null) {
                        $columnIndices['scheduled_receipt_qty'] = $colIndex;
                    }
                    if (preg_match('/po\s*no|purchase\s*order|po\s*number/i', $cellLower) && $columnIndices['po_no'] === null) {
                        $columnIndices['po_no'] = $colIndex;
                    }
                }
                
                if ($columnIndices['item_code'] !== null && $columnIndices['item_name'] !== null) {
                    $headerRow = $i;
                    break;
                }
            }

            // If header not found, use default positions
            if ($headerRow === null) {
                $headerRow = 0;
                if ($columnIndices['item_code'] === null) $columnIndices['item_code'] = 0;
                if ($columnIndices['item_name'] === null) $columnIndices['item_name'] = 1;
                if ($columnIndices['supplier_name'] === null) $columnIndices['supplier_name'] = 2;
                if ($columnIndices['scheduled_receipt_qty'] === null) $columnIndices['scheduled_receipt_qty'] = 3;
                if ($columnIndices['po_no'] === null) $columnIndices['po_no'] = 4;
            } else {
                if ($columnIndices['item_code'] === null) $columnIndices['item_code'] = 0;
                if ($columnIndices['item_name'] === null) $columnIndices['item_name'] = 1;
                if ($columnIndices['supplier_name'] === null) $columnIndices['supplier_name'] = 2;
                if ($columnIndices['scheduled_receipt_qty'] === null) $columnIndices['scheduled_receipt_qty'] = 3;
                if ($columnIndices['po_no'] === null) $columnIndices['po_no'] = 4;
            }

            // Remove header row and rows before it
            $rows = array_slice($rows, $headerRow + 1);

            $masterItems = Session::get('data_master_items', []);
            $warehouseRequests = Session::get('warehouse_requests', []);
            $historyItems = Session::get('history_items', []);
            $poItems = Session::get('data_po_items', []);
            $kedatanganItems = Session::get('kedatangan_barang_items', []);

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

            // Build PO items map for validation
            $poItemsMap = [];
            foreach ($poItems as $poItem) {
                $itemCode = strtolower(trim($poItem['item_code'] ?? ''));
                $itemName = strtolower(trim($poItem['item_name'] ?? ''));
                $poNo = trim($poItem['po_no'] ?? '');
                $scheduledQty = (int)($poItem['scheduled_receipt_qty'] ?? 0);
                
                if (!empty($itemCode) && !empty($itemName) && !empty($poNo)) {
                    $poKey = $itemCode . '|' . $itemName . '|' . $poNo;
                    if (!isset($poItemsMap[$poKey])) {
                        $poItemsMap[$poKey] = 0;
                    }
                    $poItemsMap[$poKey] += $scheduledQty;
                }
            }

            $updatedItems = 0;
            $movedToHistoryCount = 0;
            $historyDetails = [];
            $summaryItems = [];
            $newKedatanganItems = [];

            foreach ($rows as $rowIndex => $row) {
                // Skip completely empty rows
                $nonEmptyValues = array_filter($row, function($val) { 
                    if ($val === null) return false;
                    $strVal = trim((string)$val);
                    return $strVal !== '' && $strVal !== '-';
                });
                
                if (empty($nonEmptyValues)) {
                    continue;
                }

                // Get values using column indices
                $itemCode = '';
                $itemName = '';
                $supplierName = '';
                $scheduledReceiptQty = 0;
                $poNo = '';
                
                if (isset($row[$columnIndices['item_code']])) {
                    $itemCode = trim($this->getCellValue($row[$columnIndices['item_code']]));
                }
                if (isset($row[$columnIndices['item_name']])) {
                    $itemName = trim($this->getCellValue($row[$columnIndices['item_name']]));
                }
                if (isset($row[$columnIndices['supplier_name']])) {
                    $supplierName = trim($this->getCellValue($row[$columnIndices['supplier_name']]));
                }
                if (isset($row[$columnIndices['scheduled_receipt_qty']])) {
                    $scheduledReceiptQty = (int)$this->parseNumericValue($row[$columnIndices['scheduled_receipt_qty']]);
                }
                if (isset($row[$columnIndices['po_no']])) {
                    $poNo = trim($this->getCellValue($row[$columnIndices['po_no']]));
                }

                if (empty($itemCode) || empty($itemName)) {
                    continue;
                }

                // Use scheduled_receipt_qty as arrival qty
                $arrivalQty = $scheduledReceiptQty;

                if ($arrivalQty <= 0) {
                    continue;
                }

                // Validate PO No. and Qty
                $poValidation = 'valid';
                if (!empty($poNo)) {
                    $itemKeyLower = strtolower($itemCode . '|' . $itemName);
                    $poKey = $itemKeyLower . '|' . $poNo;
                    
                    if (!isset($poItemsMap[$poKey])) {
                        // PO No. not found for this item
                        $poValidation = 'invalid';
                    } else {
                        $maxQty = $poItemsMap[$poKey];
                        if ($arrivalQty > $maxQty) {
                            // Qty exceeds scheduled receipt qty
                            $poValidation = 'invalid';
                        }
                    }
                }

                $itemKey = strtolower($itemCode . '|' . $itemName);

                // Check if item exists in master data or outstanding list
                $existsInMaster = isset($masterItemsMap[$itemKey]);
                $existsInWarehouse = isset($warehouseRequestsMap[$itemKey]);

                if (!$existsInMaster && !$existsInWarehouse) {
                    continue; // Item not found, skip
                }

                // Get pengiriman_tanggal before processing (from warehouse_requests or data_master_items)
                $pengirimanTanggal = null;
                if ($existsInWarehouse && isset($warehouseRequestsMap[$itemKey])) {
                    foreach ($warehouseRequestsMap[$itemKey] as $index) {
                        $pengirimanTanggal = $warehouseRequests[$index]['pengiriman_tanggal'] ?? null;
                        if ($pengirimanTanggal) {
                            break;
                        }
                    }
                }
                if (!$pengirimanTanggal && $existsInMaster) {
                    $masterIndex = $masterItemsMap[$itemKey];
                    $pengirimanTanggal = $masterItems[$masterIndex]['pengiriman_tanggal'] ?? null;
                }

                $totalDeducted = 0;

                if ($existsInMaster) {
                    $masterIndex = $masterItemsMap[$itemKey];
                    $currentMasterOutstanding = (int) ($masterItems[$masterIndex]['outstanding'] ?? 0);
                    $currentEndingBalance = (int) ($masterItems[$masterIndex]['ending_balance'] ?? 0);

                    if ($currentMasterOutstanding > 0 && $arrivalQty > 0) {
                        $masterItems[$masterIndex]['outstanding'] = max(0, $currentMasterOutstanding - $arrivalQty);
                        $updatedItems++;
                    }

                    // Add to ending balance (barang sudah datang dan masuk stock)
                    if ($arrivalQty > 0) {
                        $masterItems[$masterIndex]['ending_balance'] = $currentEndingBalance + $arrivalQty;
                        $totalDeducted = $arrivalQty;
                    }

                    $targetWarehouseOutstanding = (int) ($masterItems[$masterIndex]['outstanding'] ?? 0);
                    $targetEndingBalance = (int) ($masterItems[$masterIndex]['ending_balance'] ?? 0);

                    if ($existsInWarehouse) {
                        if (count($warehouseRequestsMap[$itemKey]) > 0) {
                            $firstIndex = $warehouseRequestsMap[$itemKey][0];
                            $warehouseRequests[$firstIndex]['outstanding'] = $targetWarehouseOutstanding;
                            $warehouseRequests[$firstIndex]['ending_balance'] = $targetEndingBalance;

                            for ($i = 1; $i < count($warehouseRequestsMap[$itemKey]); $i++) {
                                $otherIndex = $warehouseRequestsMap[$itemKey][$i];
                                $warehouseRequests[$otherIndex]['outstanding'] = 0;
                                $warehouseRequests[$otherIndex]['ending_balance'] = $targetEndingBalance;
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
                                'ending_balance' => $targetEndingBalance,
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
                            $currentEndingBalance = (int) ($warehouseRequests[$index]['ending_balance'] ?? 0);
                            if ($currentOutstanding <= 0) {
                                continue;
                            }
                            $deductAmount = min($remainingArrival, $currentOutstanding);
                            $warehouseRequests[$index]['outstanding'] = $currentOutstanding - $deductAmount;
                            // Add to ending balance
                            $warehouseRequests[$index]['ending_balance'] = $currentEndingBalance + $deductAmount;
                            if ($deductAmount > 0) {
                                $updatedItems++;
                                $totalDeducted += $deductAmount;
                                $remainingArrival -= $deductAmount;
                            }
                        }
                    }
                }

                // Save to kedatangan_barang_items for table display
                $kedatanganItem = [
                    'id' => uniqid(),
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'supplier_name' => $supplierName,
                    'scheduled_receipt_qty' => $scheduledReceiptQty,
                    'po_no' => $poNo,
                    'arrival_date' => $arrivalDate,
                    'arrived_qty' => $arrivalQty,
                    'po_validation' => $poValidation,
                    'imported_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                ];
                $newKedatanganItems[] = $kedatanganItem;

                if ($totalDeducted > 0) {
                    // Record to history
                    $historyEntry = [
                        'id' => uniqid(),
                        'arrival_date' => $arrivalDate,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'po_no' => $poNo,
                        'scheduled_receipt_qty' => $scheduledReceiptQty,
                        'jumlah_item_datang' => $totalDeducted,
                        'pengiriman_tanggal' => $pengirimanTanggal,
                    ];
                    $historyItems[] = $historyEntry;
                    $movedToHistoryCount++;
                    $poInfo = !empty($poNo) ? " (PO: {$poNo})" : '';
                    $historyDetails[] = "{$itemCode} - {$itemName}{$poInfo}: " . number_format($totalDeducted, 0, ',', '.');

                    $summaryItems[] = [
                        'history_id' => $historyEntry['id'],
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'po_no' => $poNo,
                        'scheduled_receipt_qty' => $scheduledReceiptQty,
                        'arrived_qty' => $totalDeducted,
                        'arrival_date' => $arrivalDate,
                        'po_validation' => $poValidation,
                    ];
                }
            }

            // Merge new items with existing kedatangan items
            $kedatanganItems = array_merge($kedatanganItems, $newKedatanganItems);

            // Remove items with outstanding = 0
            $warehouseRequests = array_filter($warehouseRequests, function ($item) {
                return ($item['outstanding'] ?? 0) > 0;
            });
            $warehouseRequests = array_values($warehouseRequests);

            Session::put('data_master_items', $masterItems);
            Session::put('warehouse_requests', $warehouseRequests);
            Session::put('history_items', $historyItems);
            Session::put('kedatangan_barang_items', $kedatanganItems);
            
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


