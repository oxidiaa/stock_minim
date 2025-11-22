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

        // Sort by item code
        usort($poItems, function ($a, $b) {
            return strcmp($a['item_code'] ?? '', $b['item_code'] ?? '');
        });

        Session::put('data_po_items', $poItems);

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

            // Build map of existing items
            $existingItems = [];
            foreach ($poItems as $item) {
                $itemKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? '') . '|' . trim($item['po_no'] ?? ''));
                if (!empty($itemKey)) {
                    $existingItems[$itemKey] = $item;
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

                $itemKey = strtolower($itemCode . '|' . $itemName . '|' . $poNo);
                
                // Check if item exists
                if (isset($existingItems[$itemKey])) {
                    // Update existing item
                    foreach ($poItems as &$item) {
                        $existingKey = strtolower(trim($item['item_code'] ?? '') . '|' . trim($item['item_name'] ?? '') . '|' . trim($item['po_no'] ?? ''));
                        if ($existingKey === $itemKey) {
                            $item['supplier_name'] = $supplierName;
                            $item['scheduled_receipt_qty'] = (int) ($scheduledReceiptQty ?: 0);
                            $item['imported_at'] = $now;
                            break;
                        }
                    }
                    unset($item);
                    $updated++;
                } else {
                    // New item
                    $newPOItem = [
                        'id' => uniqid(),
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'scheduled_receipt_qty' => (int) ($scheduledReceiptQty ?: 0),
                        'po_no' => $poNo,
                        'imported_at' => $now,
                    ];

                    array_unshift($poItems, $newPOItem);
                    $existingItems[$itemKey] = $newPOItem;
                    $imported++;
                }
            }

            // Save to session
            Session::put('data_po_items', $poItems);
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

        Session::put('data_po_items', array_values($poItems));

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
}

