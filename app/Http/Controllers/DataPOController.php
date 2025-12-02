<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class DataPOController extends Controller
{
    /**
     * Display a listing of PO data items.
     */
    public function index()
    {
        $poItems = Session::get('data_po_items', []);

        // Group by Item CD, Item name, Supplier name, and PO No., then sum Sched. receipt qty.
        $groupedItems = [];
        foreach ($poItems as $item) {
            $itemCode = strtolower(trim($item['item_code'] ?? ''));
            $itemName = strtolower(trim($item['item_name'] ?? ''));
            $supplierName = strtolower(trim($item['supplier_name'] ?? ''));
            $poNo = trim($item['po_no'] ?? '');
            
            // Create unique key for grouping
            $groupKey = $itemCode . '|' . $itemName . '|' . $supplierName . '|' . $poNo;
            
            if (!isset($groupedItems[$groupKey])) {
                // First occurrence - create new grouped item
                $groupedItems[$groupKey] = [
                    'id' => $item['id'] ?? uniqid(),
                    'item_code' => $item['item_code'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'supplier_name' => $item['supplier_name'] ?? '',
                    'scheduled_receipt_qty' => (int)($item['scheduled_receipt_qty'] ?? 0),
                    'po_no' => $poNo,
                    'imported_at' => $item['imported_at'] ?? null,
                ];
            } else {
                // Add to existing group - sum the qty
                $groupedItems[$groupKey]['scheduled_receipt_qty'] += (int)($item['scheduled_receipt_qty'] ?? 0);
                // Keep the most recent imported_at if available
                if (!empty($item['imported_at']) && (empty($groupedItems[$groupKey]['imported_at']) || 
                    $item['imported_at'] > $groupedItems[$groupKey]['imported_at'])) {
                    $groupedItems[$groupKey]['imported_at'] = $item['imported_at'];
                }
            }
        }

        // Convert grouped array back to indexed array
        $poItems = array_values($groupedItems);

        // Sort by item code
        usort($poItems, function ($a, $b) {
            return strcmp($a['item_code'] ?? '', $b['item_code'] ?? '');
        });

        return view('pages.data_po', compact('poItems'));
    }

    /**
     * Import Excel file and process the PO data
     */
    public function importExcel(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('data_po.index')
                ->with('error', 'File tidak valid. Pastikan file adalah Excel (.xlsx atau .xls).');
        }

        try {
            $file = $request->file('excel_file');
            
            if (!$file || !$file->isValid()) {
                return redirect()->route('data_po.index')
                    ->with('error', 'File tidak valid atau gagal diupload.');
            }
            
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) {
                return redirect()->route('data_po.index')
                    ->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Find header row and column indices
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
                
                // Check for common header patterns
                foreach ($row as $colIndex => $cellValue) {
                    if (empty($cellValue)) {
                        continue;
                    }
                    
                    $cellLower = strtolower(trim((string)$cellValue));
                    
                    // Match column names (flexible matching)
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
                
                // If we found at least item_code and item_name, this is likely the header
                if ($columnIndices['item_code'] !== null && $columnIndices['item_name'] !== null) {
                    $headerRow = $i;
                    break;
                }
            }

            // If header not found, try default positions (A=0, B=1, C=2, D=3, E=4)
            if ($headerRow === null) {
                $headerRow = 0;
                if ($columnIndices['item_code'] === null) $columnIndices['item_code'] = 0;
                if ($columnIndices['item_name'] === null) $columnIndices['item_name'] = 1;
                if ($columnIndices['supplier_name'] === null) $columnIndices['supplier_name'] = 2;
                if ($columnIndices['scheduled_receipt_qty'] === null) $columnIndices['scheduled_receipt_qty'] = 3;
                if ($columnIndices['po_no'] === null) $columnIndices['po_no'] = 4;
            } else {
                // Even if header found, ensure all columns have defaults if not detected
                if ($columnIndices['item_code'] === null) $columnIndices['item_code'] = 0;
                if ($columnIndices['item_name'] === null) $columnIndices['item_name'] = 1;
                if ($columnIndices['supplier_name'] === null) $columnIndices['supplier_name'] = 2;
                if ($columnIndices['scheduled_receipt_qty'] === null) $columnIndices['scheduled_receipt_qty'] = 3;
                if ($columnIndices['po_no'] === null) $columnIndices['po_no'] = 4;
            }

            // Remove header row and rows before it
            $rows = array_slice($rows, $headerRow + 1);
            
            // Log column indices for troubleshooting
            \Log::info('PO Excel Import - Column indices detected', [
                'column_indices' => $columnIndices,
                'header_row' => $headerRow,
                'total_rows_after_header' => count($rows)
            ]);

            // Validate that we have required columns
            if ($columnIndices['item_code'] === null || $columnIndices['item_name'] === null) {
                return redirect()->route('data_po.index')
                    ->with('error', 'Format Excel tidak valid. Pastikan file memiliki kolom "Item CD" dan "Item name" di baris pertama.');
            }

            $poItems = Session::get('data_po_items', []);
            $now = now()->toDateTimeString();
            $imported = 0;
            $updated = 0;
            $skipped = 0;

            // Build map of existing items grouped by Item CD, Item name, Supplier name, and PO No.
            $groupedItems = [];
            foreach ($poItems as $item) {
                $itemCode = strtolower(trim($item['item_code'] ?? ''));
                $itemName = strtolower(trim($item['item_name'] ?? ''));
                $supplierName = strtolower(trim($item['supplier_name'] ?? ''));
                $poNo = trim($item['po_no'] ?? '');
                
                // Create unique key for grouping (all fields must match)
                $groupKey = $itemCode . '|' . $itemName . '|' . $supplierName . '|' . $poNo;
                
                if (!isset($groupedItems[$groupKey])) {
                    $groupedItems[$groupKey] = [
                        'id' => $item['id'] ?? uniqid(),
                        'item_code' => $item['item_code'] ?? '',
                        'item_name' => $item['item_name'] ?? '',
                        'supplier_name' => $item['supplier_name'] ?? '',
                        'scheduled_receipt_qty' => (int)($item['scheduled_receipt_qty'] ?? 0),
                        'po_no' => $poNo,
                        'imported_at' => $item['imported_at'] ?? null,
                    ];
                } else {
                    // Sum the qty for existing group
                    $groupedItems[$groupKey]['scheduled_receipt_qty'] += (int)($item['scheduled_receipt_qty'] ?? 0);
                    // Keep the most recent imported_at
                    if (!empty($item['imported_at']) && (empty($groupedItems[$groupKey]['imported_at']) || 
                        $item['imported_at'] > $groupedItems[$groupKey]['imported_at'])) {
                        $groupedItems[$groupKey]['imported_at'] = $item['imported_at'];
                    }
                }
            }

            $processedRows = 0;
            foreach ($rows as $rowIndex => $row) {
                $processedRows++;
                
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
                
                // Safely get item code
                if (isset($row[$columnIndices['item_code']])) {
                    $itemCode = trim($this->getCellValue($row[$columnIndices['item_code']]));
                }
                
                // Safely get item name
                if (isset($row[$columnIndices['item_name']])) {
                    $itemName = trim($this->getCellValue($row[$columnIndices['item_name']]));
                }
                
                // Skip if item code or name is empty
                if (empty($itemCode) || empty($itemName)) {
                    $skipped++;
                    continue;
                }
                
                // Safely get other values
                $supplierName = '';
                if (isset($row[$columnIndices['supplier_name']])) {
                    $supplierName = trim($this->getCellValue($row[$columnIndices['supplier_name']]));
                }
                
                $scheduledReceiptQty = 0;
                if (isset($row[$columnIndices['scheduled_receipt_qty']])) {
                    $scheduledReceiptQty = $this->parseNumericValue($row[$columnIndices['scheduled_receipt_qty']]);
                }
                
                $poNo = '';
                if (isset($row[$columnIndices['po_no']])) {
                    $poNo = trim($this->getCellValue($row[$columnIndices['po_no']]));
                }

                // Create group key (all fields must match for grouping)
                $groupKey = strtolower($itemCode . '|' . $itemName . '|' . $supplierName . '|' . $poNo);
                
                // Check if item group exists
                if (isset($groupedItems[$groupKey])) {
                    // Add to existing group - sum the qty
                    $groupedItems[$groupKey]['scheduled_receipt_qty'] += (int) ($scheduledReceiptQty ?: 0);
                    $groupedItems[$groupKey]['imported_at'] = $now;
                    $updated++;
                } else {
                    // New item group
                    $newPOItem = [
                        'id' => uniqid(),
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'scheduled_receipt_qty' => (int) ($scheduledReceiptQty ?: 0),
                        'po_no' => $poNo,
                        'imported_at' => $now,
                    ];

                    $groupedItems[$groupKey] = $newPOItem;
                    $imported++;
                }
            }
            
            // Convert grouped items back to array
            $poItems = array_values($groupedItems);

            // Save to session
            Session::put('data_po_items', $poItems);
            
            // Update outstanding automatically from PO data
            $this->updateOutstandingFromPO($poItems);
            
            Session::save();
            
            // Check if no items were imported or updated
            if ($imported === 0 && $updated === 0) {
                $errorMsg = 'Tidak ada data yang berhasil diimport. ';
                $errorMsg .= "Total baris dalam file: " . count($rows) . ". ";
                $errorMsg .= "Baris yang diproses: {$processedRows}. ";
                $errorMsg .= "Baris dilewati: {$skipped}. ";
                $errorMsg .= 'Pastikan file Excel memiliki format yang benar dengan kolom: Item CD, Item name, Supplier name, Sched. receipt qty., PO No.';
                
                return redirect()->route('data_po.index')
                    ->with('error', $errorMsg);
            }

            $message = "Import berhasil! {$imported} item baru ditambahkan.";
            if ($updated > 0) {
                $message .= " {$updated} item diperbarui.";
            }
            if ($skipped > 0) {
                $message .= " {$skipped} baris dilewati (Item CD atau Item name kosong).";
            }

            return redirect()->route('data_po.index')
                ->with('success', $message)
                ->with('imported_count', $imported);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            \Log::error('PO Excel read error: ' . $e->getMessage());
            return redirect()->route('data_po.index')
                ->with('error', 'Gagal membaca file Excel. Pastikan file tidak corrupt dan formatnya benar. Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('PO Import error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('data_po.index')
                ->with('error', 'Error importing file: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')');
        }
    }

    /**
     * Delete all PO items
     */
    public function deleteAll()
    {
        Session::forget('data_po_items');
        
        // Update outstanding (will be 0 since no PO items)
        $this->updateOutstandingFromPO([]);
        
        Session::save();

        return redirect()->route('data_po.index')->with('success', 'Semua data PO berhasil dihapus.');
    }

    /**
     * Delete a single PO item
     */
    public function destroy($id)
    {
        $poItems = Session::get('data_po_items', []);
        $originalCount = count($poItems);

        $poItems = array_filter($poItems, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        });

        if (count($poItems) === $originalCount) {
            return redirect()->route('data_po.index')->with('error', 'Item tidak ditemukan.');
        }

        // Re-group items after deletion
        $groupedItems = [];
        foreach ($poItems as $item) {
            $itemCode = strtolower(trim($item['item_code'] ?? ''));
            $itemName = strtolower(trim($item['item_name'] ?? ''));
            $supplierName = strtolower(trim($item['supplier_name'] ?? ''));
            $poNo = trim($item['po_no'] ?? '');
            
            $groupKey = $itemCode . '|' . $itemName . '|' . $supplierName . '|' . $poNo;
            
            if (!isset($groupedItems[$groupKey])) {
                $groupedItems[$groupKey] = [
                    'id' => $item['id'] ?? uniqid(),
                    'item_code' => $item['item_code'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'supplier_name' => $item['supplier_name'] ?? '',
                    'scheduled_receipt_qty' => (int)($item['scheduled_receipt_qty'] ?? 0),
                    'po_no' => $poNo,
                    'imported_at' => $item['imported_at'] ?? null,
                ];
            } else {
                $groupedItems[$groupKey]['scheduled_receipt_qty'] += (int)($item['scheduled_receipt_qty'] ?? 0);
            }
        }
        
        $poItems = array_values($groupedItems);
        Session::put('data_po_items', $poItems);
        
        // Update outstanding after deletion
        $this->updateOutstandingFromPO($poItems);

        return redirect()->route('data_po.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Get cell value from Excel (handles various cell types)
     */
    private function getCellValue($value)
    {
        // Handle null or empty
        if ($value === null) {
            return '';
        }
        
        // Handle empty string
        if ($value === '') {
            return '';
        }
        
        // If it's already a string, return as is (but trim)
        if (is_string($value)) {
            return trim($value);
        }
        
        // If it's numeric, convert to string
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        // If it's boolean, convert to string
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        // If it's an object, try to convert to string
        if (is_object($value)) {
            // Try __toString first
            if (method_exists($value, '__toString')) {
                return trim((string) $value);
            }
            // Handle DateTime objects
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            }
            // For PhpSpreadsheet cell objects, try to get calculated value
            if (method_exists($value, 'getCalculatedValue')) {
                $calculated = $value->getCalculatedValue();
                return $this->getCellValue($calculated);
            }
            if (method_exists($value, 'getFormattedValue')) {
                $formatted = $value->getFormattedValue();
                return $this->getCellValue($formatted);
            }
            return '';
        }
        
        // For arrays, get first element
        if (is_array($value)) {
            if (empty($value)) {
                return '';
            }
            return $this->getCellValue($value[0] ?? '');
        }
        
        return '';
    }

    /**
     * Parse numeric value from Excel cell (handles string numbers, formatted numbers, etc.)
     */
    private function parseNumericValue($value)
    {
        // First get the cell value as string
        $cellValue = $this->getCellValue($value);
        
        // Handle dash or empty values
        if (empty($cellValue) || $cellValue === '-' || trim($cellValue) === '-') {
            return 0;
        }
        
        if (is_numeric($cellValue)) {
            return (int) $cellValue;
        }
        
        if (is_string($cellValue)) {
            // Remove common formatting characters (keep digits, dots, and minus signs)
            $cleaned = preg_replace('/[^\d.-]/', '', $cellValue);
            // Remove multiple dots (keep only first one for decimal)
            $cleaned = preg_replace('/\.(?=.*\.)/', '', $cleaned);
            if (is_numeric($cleaned)) {
                return (int) $cleaned;
            }
        }
        
        return 0;
    }

    /**
     * Update outstanding from PO data (sum of scheduled_receipt_qty per item)
     */
    private function updateOutstandingFromPO($poItems)
    {
        $masterItems = Session::get('data_master_items', []);
        $warehouseRequests = Session::get('warehouse_requests', []);
        $today = now()->format('Y-m-d');
        $now = now()->toDateTimeString();

        // Build map of outstanding from PO data (sum of scheduled_receipt_qty per item)
        $poOutstandingMap = [];
        foreach ($poItems as $poItem) {
            $itemCode = strtolower(trim($poItem['item_code'] ?? ''));
            $itemName = strtolower(trim($poItem['item_name'] ?? ''));
            $itemKey = $itemCode . '|' . $itemName;
            if (!empty($itemKey)) {
                $poOutstandingMap[$itemKey] = ($poOutstandingMap[$itemKey] ?? 0) + (int)($poItem['scheduled_receipt_qty'] ?? 0);
            }
        }

        // Build map of existing items in data_master_items
        $masterItemsMap = [];
        foreach ($masterItems as $index => $item) {
            $itemKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? ''));
            if (!empty($itemKey)) {
                $masterItemsMap[$itemKey] = $index;
            }
        }

        // Build map of existing items in warehouse_requests
        $warehouseRequestsMap = [];
        foreach ($warehouseRequests as $index => $req) {
            $itemKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
            if (!empty($itemKey)) {
                if (!isset($warehouseRequestsMap[$itemKey])) {
                    $warehouseRequestsMap[$itemKey] = [];
                }
                $warehouseRequestsMap[$itemKey][] = $index;
            }
        }

        // Build map of current total outstanding from warehouse_requests
        $currentTotalOutstandingMap = [];
        foreach ($warehouseRequests as $req) {
            $itemKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
            if (!empty($itemKey)) {
                $currentTotalOutstandingMap[$itemKey] = ($currentTotalOutstandingMap[$itemKey] ?? 0) + (int)($req['outstanding'] ?? 0);
            }
        }

        // Get all unique item keys from master items and warehouse requests
        $allItemKeys = array_unique(array_merge(
            array_keys($masterItemsMap),
            array_keys($warehouseRequestsMap)
        ));

        // Update outstanding for all items
        foreach ($allItemKeys as $itemKey) {
            $calculatedOutstanding = $poOutstandingMap[$itemKey] ?? 0;
            $currentTotalOutstanding = $currentTotalOutstandingMap[$itemKey] ?? 0;
            
            // Calculate difference
            $outstandingDifference = $calculatedOutstanding - $currentTotalOutstanding;

            // Update data master if item exists
            if (isset($masterItemsMap[$itemKey])) {
                $masterIndex = $masterItemsMap[$itemKey];
                $masterItems[$masterIndex]['outstanding'] = $calculatedOutstanding;
            }

            // Update warehouse requests
            if (isset($warehouseRequestsMap[$itemKey])) {
                // Item exists in warehouse_requests, update it
                $firstIndex = $warehouseRequestsMap[$itemKey][0];
                $currentWarehouseOutstanding = (int)($warehouseRequests[$firstIndex]['outstanding'] ?? 0);
                
                // Calculate new outstanding: current + difference
                $newOutstanding = $currentWarehouseOutstanding + $outstandingDifference;
                
                // Ensure outstanding is not negative
                if ($newOutstanding < 0) {
                    $newOutstanding = 0;
                }
                
                $warehouseRequests[$firstIndex]['outstanding'] = $newOutstanding;
                
                // Set other duplicates to 0
                for ($i = 1; $i < count($warehouseRequestsMap[$itemKey]); $i++) {
                    $otherIndex = $warehouseRequestsMap[$itemKey][$i];
                    $warehouseRequests[$otherIndex]['outstanding'] = 0;
                }
            } else {
                // Item doesn't exist in warehouse_requests, add if outstanding > 0
                if ($calculatedOutstanding > 0 && isset($masterItemsMap[$itemKey])) {
                    $masterIndex = $masterItemsMap[$itemKey];
                    $masterItem = $masterItems[$masterIndex];
                    
                    $newRequest = [
                        'id' => uniqid(),
                        'request_date' => $today,
                        'item_code' => $masterItem['item_code'] ?? '',
                        'item_name' => $masterItem['item_name'] ?? '',
                        'user' => $masterItem['user'] ?? '',
                        'outstanding' => $calculatedOutstanding,
                        'outstanding_pp' => $masterItem['outstanding_pp'] ?? '',
                        'ending_balance' => (int)($masterItem['ending_balance'] ?? 0),
                        'maximal_stock' => (int)($masterItem['maximal_stock'] ?? 0),
                        'order_point' => (int)($masterItem['order_point'] ?? 0),
                        'minimal_stock' => (int)($masterItem['minimal_stock'] ?? 0),
                        'note' => null,
                        'imported_at' => $now,
                        'duplicate_note' => null,
                    ];

                    array_unshift($warehouseRequests, $newRequest);
                }
            }
        }

        // Remove items with outstanding = 0 from warehouse_requests
        $warehouseRequests = array_filter($warehouseRequests, function ($req) {
            return ($req['outstanding'] ?? 0) > 0;
        });
        $warehouseRequests = array_values($warehouseRequests);

        // Save updated data
        Session::put('data_master_items', $masterItems);
        Session::put('warehouse_requests', $warehouseRequests);
    }
}

