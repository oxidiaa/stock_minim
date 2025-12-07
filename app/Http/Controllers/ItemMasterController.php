<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemMaster;
use App\Models\ItemOutstanding;
use App\Models\DataPO;
use App\Models\History;
use App\Models\KedatanganBarang; // For deletion logic
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemMasterController extends Controller
{
    /**
     * Display a listing of data master items.
     */
    public function index()
    {
        // Eloquent: Get all items ordered by item_code
        $masterItems = ItemMaster::orderBy('item_code')->get();

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
                    
                    if (preg_match('/^user\s*$/i', $cellLower) && $columnIndices['user'] === null) {
                        $columnIndices['user'] = $colIndex;
                    }
                    if (preg_match('/outstanding\s*pp|outstandingpp/i', $cellLower) && $columnIndices['outstanding_pp'] === null) {
                        $columnIndices['outstanding_pp'] = $colIndex;
                    }
                }
                
                if ($columnIndices['item_code'] !== null && $columnIndices['item_name'] !== null) {
                    $headerRow = $i;
                    break;
                }
            }

            // Defaults if header not found
            if ($headerRow === null) {
                $headerRow = 0;
                $columnIndices['item_code'] = $columnIndices['item_code'] ?? 0;
                $columnIndices['item_name'] = $columnIndices['item_name'] ?? 1;
                $columnIndices['outstanding'] = $columnIndices['outstanding'] ?? 2;
                $columnIndices['ending_balance'] = $columnIndices['ending_balance'] ?? 3;
                $columnIndices['maximal_stock'] = $columnIndices['maximal_stock'] ?? 4;
                $columnIndices['order_point'] = $columnIndices['order_point'] ?? 5;
                $columnIndices['minimal_stock'] = $columnIndices['minimal_stock'] ?? 6;
                $columnIndices['user'] = $columnIndices['user'] ?? 7;
                $columnIndices['outstanding_pp'] = $columnIndices['outstanding_pp'] ?? 8;
            } else {
                $columnIndices['item_code'] = $columnIndices['item_code'] ?? 0;
                $columnIndices['item_name'] = $columnIndices['item_name'] ?? 1;
                $columnIndices['outstanding'] = $columnIndices['outstanding'] ?? 2;
                $columnIndices['ending_balance'] = $columnIndices['ending_balance'] ?? 3;
                $columnIndices['maximal_stock'] = $columnIndices['maximal_stock'] ?? 4;
                $columnIndices['order_point'] = $columnIndices['order_point'] ?? 5;
                $columnIndices['minimal_stock'] = $columnIndices['minimal_stock'] ?? 6;
                $columnIndices['user'] = $columnIndices['user'] ?? 7; 
                $columnIndices['outstanding_pp'] = $columnIndices['outstanding_pp'] ?? 8;
            }

            // Remove header row
            $rows = array_slice($rows, $headerRow + 1);
            
            Log::info('Excel Import - Column indices', $columnIndices);

            if ($columnIndices['item_code'] === null || $columnIndices['item_name'] === null) {
                return redirect()->route('item_master.index')
                    ->with('error', 'Format Excel tidak valid. Pastikan file memiliki kolom "Item Code" dan "Description/Item Name".');
            }
            
            $today = now()->format('Y-m-d');
            $imported = 0;
            $updated = 0;
            $movedToOutstanding = 0;
            $skipped = 0;
            $processedRows = 0;

            // Pre-fetch relevant data to minimize queries (optional optimization)
            // For simplicity in SQLite with moderate data, direct queries are okay, 
            // but let's cache PO sums.
            
            // Build map of PO outstanding: item_code|item_name -> sum(scheduled_receipt_qty)
            // Note: DataPO table might have duplicate items, we sum by scheduled_receipt_qty.
            // Using DB query for aggregation.
            $poSums = DataPO::select('item_code', 'item_name', DB::raw('SUM(scheduled_receipt_qty) as total_qty'))
                ->groupBy('item_code', 'item_name')
                ->get()
                ->mapWithKeys(function ($item) {
                     $key = strtolower(trim($item->item_code) . '|' . trim($item->item_name));
                     return [$key => (int)$item->total_qty];
                });

            // Start processing rows
            DB::beginTransaction();
            try {
                foreach ($rows as $rowIndex => $row) {
                    $processedRows++;
                    
                    // Skip empty rows
                    if (empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== '' && trim((string)$v) !== '-'))) {
                        continue;
                    }

                    $itemCode = isset($row[$columnIndices['item_code']]) ? trim($this->getCellValue($row[$columnIndices['item_code']])) : '';
                    $itemName = isset($row[$columnIndices['item_name']]) ? trim($this->getCellValue($row[$columnIndices['item_name']])) : '';
                    
                    if (empty($itemCode) || empty($itemName)) {
                        $skipped++;
                        continue;
                    }

                    // PO Calculated Outstanding
                    $itemKey = strtolower($itemCode . '|' . $itemName);
                    $calculatedOutstanding = $poSums[$itemKey] ?? 0;
                    
                    // Parse other fields
                    $endingBalance = $this->parseNumericValue($row[$columnIndices['ending_balance']] ?? null);
                    $maximalStock = $this->parseNumericValue($row[$columnIndices['maximal_stock']] ?? null);
                    $orderPoint = $this->parseNumericValue($row[$columnIndices['order_point']] ?? null);
                    $minimalStock = $this->parseNumericValue($row[$columnIndices['minimal_stock']] ?? null);
                    $user = trim($this->getCellValue($row[$columnIndices['user']] ?? ''));
                    $outstandingPp = trim($this->getCellValue($row[$columnIndices['outstanding_pp']] ?? ''));
                    
                    // Convert imported_at to proper datetime
                    $now = Carbon::now('Asia/Jakarta');

                    // UPDATE OR CREATE ITEM MASTER
                    $masterItem = ItemMaster::where('item_code', $itemCode)
                        ->where('item_name', $itemName)
                        ->first();

                    if ($masterItem) {
                        // Update
                        $masterItem->outstanding = $calculatedOutstanding;
                        $masterItem->ending_balance = $endingBalance;
                        $masterItem->maximal_stock = $maximalStock;
                        $masterItem->order_point = $orderPoint;
                        $masterItem->minimal_stock = $minimalStock;
                        $masterItem->user = $user;
                        $masterItem->outstanding_pp = $outstandingPp;
                        
                        if ($masterItem->isDirty()) {
                            $masterItem->imported_at = $now;
                            $masterItem->save();
                            $updated++;
                        }
                    } else {
                        // Create
                        ItemMaster::create([
                            'item_code' => $itemCode,
                            'item_name' => $itemName,
                            'outstanding' => $calculatedOutstanding,
                            'ending_balance' => $endingBalance,
                            'maximal_stock' => $maximalStock,
                            'order_point' => $orderPoint,
                            'minimal_stock' => $minimalStock,
                            'user' => $user,
                            'outstanding_pp' => $outstandingPp,
                            'imported_at' => $now,
                        ]);
                        $imported++;
                    }

                    // SYNC WITH ITEM OUTSTANDING
                    if ($calculatedOutstanding > 0) {
                        // Get current total outstanding in ItemOutstanding table for this item
                        $currentTotalOutstanding = ItemOutstanding::where('item_code', $itemCode)
                            ->where('item_name', $itemName)
                            ->sum('outstanding');

                        $outstandingDifference = $calculatedOutstanding - $currentTotalOutstanding;

                        // Find existing request
                        $existingReq = ItemOutstanding::where('item_code', $itemCode)
                            ->where('item_name', $itemName)
                            ->orderBy('request_date', 'desc') // Update most recent? Or just first one?
                            ->first();

                        if ($existingReq) {
                            $newOutstanding = $existingReq->outstanding + $outstandingDifference;
                            if ($newOutstanding < 0) $newOutstanding = 0;

                            $existingReq->outstanding = $newOutstanding;
                            $existingReq->user = $user;
                            $existingReq->outstanding_pp = $outstandingPp;
                            $existingReq->ending_balance = $endingBalance;
                            $existingReq->maximal_stock = $maximalStock;
                            $existingReq->order_point = $orderPoint;
                            $existingReq->minimal_stock = $minimalStock;
                            $existingReq->imported_at = $now;
                            $existingReq->save();
                        } else {
                            // Create new request
                            ItemOutstanding::create([
                                'request_date' => $today,
                                'item_code' => $itemCode,
                                'item_name' => $itemName,
                                'user' => $user,
                                'outstanding' => $calculatedOutstanding,
                                'outstanding_pp' => $outstandingPp,
                                'ending_balance' => $endingBalance,
                                'maximal_stock' => $maximalStock,
                                'order_point' => $orderPoint,
                                'minimal_stock' => $minimalStock,
                                'imported_at' => $now,
                            ]);
                            $movedToOutstanding++;
                        }
                    } else {
                        // Outstanding 0, should we delete from ItemOutstanding?
                        // Old logic: "Remove items with outstanding = 0 from warehouse_requests"
                        // So yes, we should delete them 
                        // But maybe only if they become 0? 
                        // The old logic filtered the array at the end.
                        // We will delete rows with outstanding=0 for this item?
                        // Actually, if Calculated is 0, then we should probably set all requests to 0 or delete them.
                        // Let's set them to 0 for now or delete?
                        // "Remove items with outstanding = 0" implies deletion.
                        ItemOutstanding::where('item_code', $itemCode)
                            ->where('item_name', $itemName)
                            ->delete();
                    }
                }
                
                // Cleanup: Delete any item_outstandings that have 0 outstanding (globally clean up just in case)
                ItemOutstanding::where('outstanding', '<=', 0)->delete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            if ($imported === 0 && $updated === 0) {
                 return redirect()->route('item_master.index')
                    ->with('error', 'Tidak ada data yang berhasil diimport atau diperbarui. Pastikan format valid.');
            }

            $message = "Import berhasil! {$imported} item baru, {$updated} item diperbarui.";
            if ($movedToOutstanding > 0) $message .= " {$movedToOutstanding} item masuk ke Outstanding.";
            
            return redirect()->route('item_master.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return redirect()->route('item_master.index')
                ->with('error', 'Error: ' . $e->getMessage());
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

        $item = ItemMaster::find($id);
        if ($item) {
            $item->note = $validated['note'] ?? null;
            $item->save();
            return response()->json(['success' => true, 'message' => 'Note berhasil diperbarui']);
        }

        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
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

        $item = ItemMaster::find($id);
        if (!$item) {
            return redirect()->route('item_master.index')->with('error', 'Item tidak ditemukan.');
        }

        $item->update($validated); // Mass assign

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

        DB::beginTransaction();
        try {
            if (in_array('data_master', $pages)) {
                ItemMaster::truncate();
                $deletedPages[] = 'Data Master';
            }

            if (in_array('item_outstanding', $pages)) {
                ItemOutstanding::truncate();
                $deletedPages[] = 'Item Outstanding';
            }

            if (in_array('history', $pages)) {
                History::truncate();
                $deletedPages[] = 'History';
            }

            if (in_array('import_summary', $pages)) {
                // Session based summaries
                Session::forget('processing_import_summary');
                Session::forget('kedatangan_import_summary');
                $deletedPages[] = 'Import Summary';
            }

            if (in_array('data_po', $pages)) {
                DataPO::truncate();
                $deletedPages[] = 'Data PO';
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('item_master.index')->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }

        if (empty($deletedPages)) {
            return redirect()->route('item_master.index')
                ->with('error', 'Tidak ada halaman yang dipilih untuk dihapus.');
        }

        return redirect()->route('item_master.index')->with('success', 'Data berhasil dihapus dari: ' . implode(', ', $deletedPages) . '.');
    }

    /**
     * Delete a master item.
     */
    public function destroy($id)
    {
        $item = ItemMaster::find($id);
        if (!$item) {
             return redirect()->route('item_master.index')->with('error', 'Item tidak ditemukan.');
        }
        $item->delete();

        return redirect()->route('item_master.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Get cell value from Excel (handles various cell types)
     */
    private function getCellValue($value)
    {
        if ($value === null) return '';
        if ($value === '') return '';
        if (is_string($value)) return trim($value);
        if (is_numeric($value)) return (string) $value;
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_object($value)) {
            if (method_exists($value, '__toString')) return trim((string) $value);
            return '';
        }
        if (is_array($value)) return $this->getCellValue($value[0] ?? '');
        return '';
    }

    /**
     * Parse numeric value from Excel cell
     */
    private function parseNumericValue($value)
    {
        $cellValue = $this->getCellValue($value);
        if (empty($cellValue) || $cellValue === '-' || trim($cellValue) === '-') return 0;
        if (is_numeric($cellValue)) return (int) $cellValue;
        
        $cleaned = preg_replace('/[^\d.-]/', '', $cellValue);
        $cleaned = preg_replace('/\.(?=.*\.)/', '', $cleaned);
        if (is_numeric($cleaned)) return (int) $cleaned;
        
        return 0;
    }
}

