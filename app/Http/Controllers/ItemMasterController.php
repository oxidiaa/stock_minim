<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ItemMasterController extends Controller
{
    /**
     * Display a listing of data master items.
     */
    public function index()
    {
        $masterItems = Session::get('data_master_items', []);

        // Ensure all items have maximal_stock field (for backward compatibility)
        foreach ($masterItems as &$item) {
            if (!isset($item['maximal_stock'])) {
                $item['maximal_stock'] = 0;
            }
        }
        unset($item);

        // Sort by item code
        usort($masterItems, function ($a, $b) {
            return strcmp($a['item_code'] ?? '', $b['item_code'] ?? '');
        });

        Session::put('data_master_items', $masterItems);

        return view('pages.item_master', compact('masterItems'));
    }

    /**
     * Import Excel file and process the data
     */
    public function importExcel(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('item_master.index')
                ->with('error', 'File tidak valid. Pastikan file adalah Excel (.xlsx atau .xls).');
        }

        try {
            $file = $request->file('excel_file');
            
            if (!$file || !$file->isValid()) {
                return redirect()->route('item_master.index')
                    ->with('error', 'File tidak valid atau gagal diupload.');
            }
            
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) {
                return redirect()->route('item_master.index')
                    ->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Find header row and column indices
            $headerRow = null;
            $columnIndices = [
                'item_code' => null,
                'item_name' => null,
                'outstanding' => null,
                'ending_balance' => null,
                'maximal_stock' => null,
                'order_point' => null,
                'minimal_stock' => null,
                'user' => null,
                'outstanding_pp' => null,
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
                    if (preg_match('/item\s*code|kode\s*item|code/i', $cellLower) && $columnIndices['item_code'] === null) {
                        $columnIndices['item_code'] = $colIndex;
                    }
                    if (preg_match('/description|deskripsi|item\s*name|nama\s*item|name/i', $cellLower) && $columnIndices['item_name'] === null) {
                        $columnIndices['item_name'] = $colIndex;
                    }
                    if (preg_match('/outstanding/i', $cellLower) && $columnIndices['outstanding'] === null && !preg_match('/pp/i', $cellLower)) {
                        $columnIndices['outstanding'] = $colIndex;
                    }
                    if (preg_match('/ending\s*balance|balance/i', $cellLower) && $columnIndices['ending_balance'] === null) {
                        $columnIndices['ending_balance'] = $colIndex;
                    }
                    if (preg_match('/^max$|^max\s*$|max\s*stock|maximal/i', $cellLower) && $columnIndices['maximal_stock'] === null) {
                        $columnIndices['maximal_stock'] = $colIndex;
                    }
                    if (preg_match('/order\s*point|orderpoint/i', $cellLower) && $columnIndices['order_point'] === null) {
                        $columnIndices['order_point'] = $colIndex;
                    }
                    if (preg_match('/^min$|min\s*stock|minimal/i', $cellLower) && $columnIndices['minimal_stock'] === null) {
                        $columnIndices['minimal_stock'] = $colIndex;
                    }
                    // Match user column (flexible matching - "user", "User", "USER", with or without spaces)
                    if (preg_match('/^user\s*$/i', $cellLower) && $columnIndices['user'] === null) {
                        $columnIndices['user'] = $colIndex;
                    }
                    if (preg_match('/outstanding\s*pp|outstandingpp/i', $cellLower) && $columnIndices['outstanding_pp'] === null) {
                        $columnIndices['outstanding_pp'] = $colIndex;
                    }
                }
                
                // If we found at least item_code and item_name, this is likely the header
                if ($columnIndices['item_code'] !== null && $columnIndices['item_name'] !== null) {
                    $headerRow = $i;
                    break;
                }
            }

            // If header not found, try default positions (A=0, B=1, C=2, etc.)
            if ($headerRow === null) {
                $headerRow = 0;
                if ($columnIndices['item_code'] === null) $columnIndices['item_code'] = 0;
                if ($columnIndices['item_name'] === null) $columnIndices['item_name'] = 1;
                if ($columnIndices['outstanding'] === null) $columnIndices['outstanding'] = 2;
                if ($columnIndices['ending_balance'] === null) $columnIndices['ending_balance'] = 3;
                if ($columnIndices['maximal_stock'] === null) $columnIndices['maximal_stock'] = 4;
                if ($columnIndices['order_point'] === null) $columnIndices['order_point'] = 5;
                if ($columnIndices['minimal_stock'] === null) $columnIndices['minimal_stock'] = 6;
                if ($columnIndices['user'] === null) $columnIndices['user'] = 7;
                if ($columnIndices['outstanding_pp'] === null) $columnIndices['outstanding_pp'] = 8;
            } else {
                // Even if header found, ensure all columns have defaults if not detected
                // This handles cases where header names don't match exactly
                if ($columnIndices['item_code'] === null) $columnIndices['item_code'] = 0;
                if ($columnIndices['item_name'] === null) $columnIndices['item_name'] = 1;
                if ($columnIndices['outstanding'] === null) $columnIndices['outstanding'] = 2;
                if ($columnIndices['ending_balance'] === null) $columnIndices['ending_balance'] = 3;
                if ($columnIndices['maximal_stock'] === null) $columnIndices['maximal_stock'] = 4;
                if ($columnIndices['order_point'] === null) $columnIndices['order_point'] = 5;
                if ($columnIndices['minimal_stock'] === null) $columnIndices['minimal_stock'] = 6;
                if ($columnIndices['user'] === null) $columnIndices['user'] = 7; // Default to column H
                if ($columnIndices['outstanding_pp'] === null) $columnIndices['outstanding_pp'] = 8;
            }

            // Remove header row and rows before it
            $rows = array_slice($rows, $headerRow + 1);
            
            // Log column indices for troubleshooting
            \Log::info('Excel Import - Column indices detected', [
                'column_indices' => $columnIndices,
                'header_row' => $headerRow,
                'total_rows_after_header' => count($rows)
            ]);

            // Validate that we have required columns (Item Code and Description are mandatory)
            if ($columnIndices['item_code'] === null || $columnIndices['item_name'] === null) {
                return redirect()->route('item_master.index')
                    ->with('error', 'Format Excel tidak valid. Pastikan file memiliki kolom "Item Code" dan "Description/Item Name" di baris pertama.');
            }
            
            // Ensure user column has a position (use default if not detected)
            if ($columnIndices['user'] === null) {
                $columnIndices['user'] = 6; // Default to column G
            }

            $masterItems = Session::get('data_master_items', []);
            $warehouseRequests = Session::get('warehouse_requests', []);
            $today = now()->format('Y-m-d');
            $imported = 0;
            $updated = 0;
            $movedToOutstanding = 0;
            $skipped = 0;

            // Build map of existing items in data_master_items
            $existingMasterItems = [];
            foreach ($masterItems as $item) {
                $itemKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    $existingMasterItems[$itemKey] = $item;
                }
            }

            // Build map of existing items in warehouse_requests
            $existingOutstandingItems = [];
            foreach ($warehouseRequests as $req) {
                $itemKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    $existingOutstandingItems[$itemKey] = true;
                }
            }

            // Build map of total outstanding from item outstanding list
            $totalOutstandingMap = [];
            
            // Count outstanding from warehouse_requests
            foreach ($warehouseRequests as $req) {
                $itemKey = strtolower(trim($req['item_code'] ?? '') . '|' . trim($req['item_name'] ?? ''));
                if (!empty($itemKey)) {
                    $totalOutstandingMap[$itemKey] = ($totalOutstandingMap[$itemKey] ?? 0) + (int) ($req['outstanding'] ?? 0);
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

                // Get values using column indices (flexible column positions)
                // Use null coalescing and array access with isset check to prevent errors
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
                    \Log::debug("Row {$rowIndex} skipped - empty item code or name", [
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'row_data' => array_slice($row, 0, 5)
                    ]);
                    continue;
                }
                
                // Safely get numeric values
                $excelOutstanding = 0;
                if (isset($row[$columnIndices['outstanding']])) {
                    $excelOutstanding = $this->parseNumericValue($row[$columnIndices['outstanding']]);
                }
                
                $endingBalance = 0;
                if (isset($row[$columnIndices['ending_balance']])) {
                    $endingBalance = $this->parseNumericValue($row[$columnIndices['ending_balance']]);
                }
                
                // Get MAX value - use detected column or default position 4 (column E)
                $maxColIndex = $columnIndices['maximal_stock'] ?? 4;
                $maximalStock = isset($row[$maxColIndex]) ? $this->parseNumericValue($row[$maxColIndex]) : 0;
                
                $orderPoint = 0;
                if (isset($row[$columnIndices['order_point']])) {
                    $orderPoint = $this->parseNumericValue($row[$columnIndices['order_point']]);
                }
                
                $minimalStock = 0;
                if (isset($row[$columnIndices['minimal_stock']])) {
                    $minimalStock = $this->parseNumericValue($row[$columnIndices['minimal_stock']]);
                }
                
                // Get user value - always try to read from the column position
                $userValue = '';
                $userColIndex = $columnIndices['user'] ?? 7;
                if (isset($row[$userColIndex])) {
                    $rawUserValue = $row[$userColIndex];
                    $userValue = $this->getCellValue($rawUserValue);
                }
                $user = trim($userValue);
                
                // If user is empty, use empty string (don't skip the row)
                if (empty($user)) {
                    $user = '';
                }
                
                $outstandingPpColIndex = $columnIndices['outstanding_pp'] ?? 8;
                $outstandingPp = isset($row[$outstandingPpColIndex]) ? trim($this->getCellValue($row[$outstandingPpColIndex])) : '';

                $itemKey = strtolower($itemCode . '|' . $itemName);
                $now = now()->toDateTimeString();
                
                // Check if item exists in data master
                if (isset($existingMasterItems[$itemKey])) {
                    $existingItem = $existingMasterItems[$itemKey];
                    
                    // Check if data has changed
                    $hasChanged = false;
                    if (
                        (int)($existingItem['outstanding'] ?? 0) !== $excelOutstanding ||
                        (int)($existingItem['ending_balance'] ?? 0) !== (int)$endingBalance ||
                        (int)($existingItem['maximal_stock'] ?? 0) !== (int)$maximalStock ||
                        (int)($existingItem['order_point'] ?? 0) !== (int)$orderPoint ||
                        (int)($existingItem['minimal_stock'] ?? 0) !== (int)$minimalStock ||
                        ($existingItem['user'] ?? '') !== $user ||
                        ($existingItem['outstanding_pp'] ?? '') !== $outstandingPp
                    ) {
                        $hasChanged = true;
                    }
                    
                    // Update existing item in data master
                    foreach ($masterItems as &$item) {
                        $existingKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? ''));
                        if ($existingKey === $itemKey) {
                            // Preserve existing imported_at if no change
                            $existingImportedAt = $item['imported_at'] ?? null;
                            
                            $item['outstanding'] = $excelOutstanding;
                            $item['ending_balance'] = (int) ($endingBalance ?: 0);
                            $item['maximal_stock'] = (int) ($maximalStock ?: 0);
                            $item['order_point'] = (int) ($orderPoint ?: 0);
                            $item['minimal_stock'] = (int) ($minimalStock ?: 0);
                            $item['user'] = $user;
                            $item['outstanding_pp'] = $outstandingPp;
                            
                            // Update import date only if data changed, otherwise keep existing date
                            if ($hasChanged) {
                                $item['imported_at'] = $now;
                            } else {
                                $item['imported_at'] = $existingImportedAt;
                            }
                            
                            break;
                        }
                    }
                    unset($item);
                    $updated++;
                } else {
                    // New item in data master
                    $newMasterItem = [
                        'id' => uniqid(),
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'outstanding' => $excelOutstanding,
                        'ending_balance' => (int) ($endingBalance ?: 0),
                        'maximal_stock' => (int) ($maximalStock ?: 0),
                        'order_point' => (int) ($orderPoint ?: 0),
                        'minimal_stock' => (int) ($minimalStock ?: 0),
                        'user' => $user,
                        'outstanding_pp' => $outstandingPp,
                        'note' => null,
                        'imported_at' => $now,
                    ];

                    array_unshift($masterItems, $newMasterItem);
                    $existingMasterItems[$itemKey] = $newMasterItem;
                    $imported++;
                }

                // If item has outstanding > 0, add/update it in item outstanding
                if ($excelOutstanding > 0) {
                    // Get current total outstanding from all pages
                    $currentTotalOutstanding = $totalOutstandingMap[$itemKey] ?? 0;
                    
                    // Calculate difference: excel outstanding - current total outstanding
                    $outstandingDifference = $excelOutstanding - $currentTotalOutstanding;

                    if (isset($existingOutstandingItems[$itemKey])) {
                        // Item exists in warehouse_requests, update it
                        foreach ($warehouseRequests as &$req) {
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
                                $req['user'] = $user;
                                $req['outstanding_pp'] = $outstandingPp;
                                $req['ending_balance'] = (int) ($endingBalance ?: 0);
                                $req['maximal_stock'] = (int) ($maximalStock ?: 0);
                                $req['order_point'] = (int) ($orderPoint ?: 0);
                                $req['minimal_stock'] = (int) ($minimalStock ?: 0);
                                $req['imported_at'] = $now;
                                break;
                            }
                        }
                        unset($req);
                    } else {
                        // Item doesn't exist in warehouse_requests, add with full outstanding
                        $newOutstanding = $excelOutstanding;
                        
                        $newRequest = [
                            'id' => uniqid(),
                            'request_date' => $today,
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
                            'imported_at' => $now,
                            'duplicate_note' => null,
                        ];

                        array_unshift($warehouseRequests, $newRequest);
                        $existingOutstandingItems[$itemKey] = true;
                        $movedToOutstanding++;
                    }
                }
            }

            // Remove items with outstanding = 0 from warehouse_requests
            $warehouseRequests = array_filter($warehouseRequests, function ($req) {
                return ($req['outstanding'] ?? 0) > 0;
            });
            $warehouseRequests = array_values($warehouseRequests);

            // Ensure all master items have maximal_stock field
            foreach ($masterItems as &$item) {
                if (!isset($item['maximal_stock'])) {
                    $item['maximal_stock'] = 0;
                }
            }
            unset($item);

            // Save to session
            Session::put('data_master_items', $masterItems);
            Session::put('warehouse_requests', $warehouseRequests);
            
            // Force session save
            Session::save();
            
            // Verify data was saved
            $savedItems = Session::get('data_master_items', []);
            \Log::info('Excel Import - Data saved to session', [
                'imported' => $imported,
                'updated' => $updated,
                'total_items_in_session' => count($savedItems),
                'first_item_sample' => !empty($savedItems) ? array_slice($savedItems[0], 0, 5) : null
            ]);

            // Check if no items were imported or updated
            if ($imported === 0 && $updated === 0) {
                $errorMsg = 'Tidak ada data yang berhasil diimport. ';
                $errorMsg .= "Total baris dalam file: " . count($rows) . ". ";
                $errorMsg .= "Baris yang diproses: {$processedRows}. ";
                $errorMsg .= "Baris dilewati: {$skipped}. ";
                $errorMsg .= 'Pastikan file Excel memiliki format yang benar dengan kolom: Item Code, Description, Outstanding, Ending Balance, MAX, ORDER POINT, MIN, USER, Outstanding PP. ';
                $errorMsg .= 'Pastikan Item Code dan Description tidak kosong.';
                
                \Log::warning('Import failed - no data imported', [
                    'total_rows' => count($rows),
                    'processed_rows' => $processedRows,
                    'skipped' => $skipped,
                    'column_indices' => $columnIndices,
                    'header_row' => $headerRow ?? 'not found'
                ]);
                
                return redirect()->route('item_master.index')
                    ->with('error', $errorMsg);
            }

            $message = "Import berhasil! {$imported} item baru ditambahkan ke data master.";
            if ($updated > 0) {
                $message .= " {$updated} item diperbarui di data master.";
            }
            if ($movedToOutstanding > 0) {
                $message .= " {$movedToOutstanding} item dengan outstanding > 0 ditambahkan ke item outstanding.";
            }
            if ($skipped > 0) {
                $message .= " {$skipped} baris dilewati (Item Code atau Description kosong).";
            }
            
            \Log::info('Excel Import - Success', [
                'imported' => $imported,
                'updated' => $updated,
                'moved_to_outstanding' => $movedToOutstanding,
                'skipped' => $skipped
            ]);

            return redirect()->route('item_master.index')
                ->with('success', $message)
                ->with('imported_count', $imported);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            \Log::error('Excel read error: ' . $e->getMessage());
            return redirect()->route('item_master.index')
                ->with('error', 'Gagal membaca file Excel. Pastikan file tidak corrupt dan formatnya benar. Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('item_master.index')
                ->with('error', 'Error importing file: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')');
        }
    }

    /**
     * Update note for a master item.
     */
    public function updateNote(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $masterItems = Session::get('data_master_items', []);
        foreach ($masterItems as &$item) {
            if (($item['id'] ?? '') === $id) {
                $item['note'] = $validated['note'] ?? null;
                break;
            }
        }
        unset($item);

        Session::put('data_master_items', $masterItems);

        return response()->json([
            'success' => true,
            'message' => 'Note berhasil diperbarui',
        ]);
    }

    /**
     * Update a master item.
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
            'user' => 'required|string|max:255',
            'outstanding_pp' => 'nullable|string|max:255',
        ]);

        $masterItems = Session::get('data_master_items', []);
        $found = false;

        foreach ($masterItems as &$item) {
            if (($item['id'] ?? '') === $id) {
                $item['item_code'] = $validated['item_code'];
                $item['item_name'] = $validated['item_name'];
                $item['outstanding'] = $validated['outstanding'];
                $item['ending_balance'] = $validated['ending_balance'];
                $item['maximal_stock'] = $validated['maximal_stock'];
                $item['order_point'] = $validated['order_point'];
                $item['minimal_stock'] = $validated['minimal_stock'];
                $item['user'] = $validated['user'];
                $item['outstanding_pp'] = $validated['outstanding_pp'] ?? '';
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            return redirect()->route('item_master.index')->with('error', 'Item tidak ditemukan.');
        }

        Session::put('data_master_items', $masterItems);

        return redirect()->route('item_master.index')->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Delete all items from selected pages
     */
    public function deleteAllItems(Request $request)
    {
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*' => 'in:data_master,item_outstanding,history,import_summary,data_po',
        ]);

        $pages = $validated['pages'];
        $deletedPages = [];

        // Delete Data Master
        if (in_array('data_master', $pages)) {
            Session::forget('data_master_items');
            $deletedPages[] = 'Data Master';
        }

        // Delete Item Outstanding
        if (in_array('item_outstanding', $pages)) {
            Session::forget('warehouse_requests');
            $deletedPages[] = 'Item Outstanding';
        }

        // Delete History
        if (in_array('history', $pages)) {
            Session::forget('history_items');
            $deletedPages[] = 'History';
        }

        // Delete Import Summary
        if (in_array('import_summary', $pages)) {
            Session::forget('processing_import_summary');
            Session::forget('kedatangan_import_summary');
            $deletedPages[] = 'Import Summary';
        }

        // Delete Data PO
        if (in_array('data_po', $pages)) {
            Session::forget('data_po_items');
            $deletedPages[] = 'Data PO';
        }

        if (empty($deletedPages)) {
            return redirect()->route('item_master.index')
                ->with('error', 'Tidak ada halaman yang dipilih untuk dihapus.');
        }

        $message = 'Data berhasil dihapus dari: ' . implode(', ', $deletedPages) . '.';

        return redirect()->route('item_master.index')->with('success', $message);
    }

    /**
     * Delete a master item.
     */
    public function destroy($id)
    {
        $masterItems = Session::get('data_master_items', []);
        $originalCount = count($masterItems);

        $masterItems = array_filter($masterItems, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        });

        if (count($masterItems) === $originalCount) {
            return redirect()->route('item_master.index')->with('error', 'Item tidak ditemukan.');
        }

        Session::put('data_master_items', array_values($masterItems));

        return redirect()->route('item_master.index')->with('success', 'Item berhasil dihapus.');
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
}

