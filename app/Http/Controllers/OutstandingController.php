<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ItemOutstanding;
use App\Models\DataPO;
use App\Models\ItemMaster;

class OutstandingController extends Controller
{
    /**
     * Check if user is master - only master can access item outstanding
     */
    private function checkMasterAccess()
    {
        if (!auth()->check() || auth()->user()->username !== 'master') {
            abort(403, 'Akses ditolak. Hanya user master yang dapat mengakses halaman ini.');
        }
    }

    /**
     * Display a listing of item outstanding requests.
     */
    public function index()
    {
        // Check if user is master - only master can access item outstanding
        $this->checkMasterAccess();

        // Fetch all outstanding requests
        $requests = ItemOutstanding::orderBy('request_date', 'desc')
                                   ->orderBy('created_at', 'desc')
                                   ->get(); // Collection of models

        // Fetch all PO items for mapping
        // We fetching all might be heavy if DataPO is huge, but for now it's migration parity.
        // Optimization: Fetch only for items in requests.
        $itemCodes = $requests->pluck('item_code')->unique()->toArray();
        $poItems = DataPO::whereIn('item_code', $itemCodes)->get();

        // Convert collection to array or use as is. Original code modified the array structure with 'po_data'.
        // We can append attributes to the models or transform to array.
        // Transforming to array is safer to match view expectations if view uses array syntax $req['po_data'].
        // However, objects allow ->po_data assignment too.
        // Let's use map to attach data.

        // Group POs by item_code|item_name for faster lookup
        $poLookup = [];
        foreach ($poItems as $po) {
            $key = strtolower(trim($po->item_code) . '|' . trim($po->item_name));
            if (!isset($poLookup[$key])) {
                $poLookup[$key] = [];
            }
            $poLookup[$key][] = $po;
        }

        $processedRequests = $requests->map(function ($req) use ($poLookup) {
            $reqKey = strtolower(trim($req->item_code) . '|' . trim($req->item_name));
            
            $matchingPOs = $poLookup[$reqKey] ?? [];

            // Group by PO NO
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

            // Manually attach these properties to the model instance (or convert to array)
            // If view uses $req['po_data'], this assumes array access. 
            // Eloquent models support array access but custom attributes need setup.
            // Let's rely on the fact that we pass $requests to view.
            // If view code is $req['po_data'], we need to make sure it works.
            // Safest is to convert to array.
            
            $reqArray = $req->toArray();
            $reqArray['po_data'] = array_values($poGroups);
            $reqArray['total_receipt_qty'] = array_sum(array_column($poGroups, 'total_qty'));
            $reqArray['has_multiple_po'] = count($poGroups) > 1;
            
            // Ensure proper casting/defaults for view if not in DB
            // (DB columns should handle this via casts, but array conversion keeps them)
            
            return $reqArray;
        });

        // The view expects an array of arrays or collection of arrays?
        // Original: Session::get returns array of arrays.
        // So $requests should be array or collection of arrays.
        // $processedRequests is a collection of arrays.
        
        return view('pages.item_outstanding', ['requests' => $processedRequests->all()]);
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        $this->checkMasterAccess();
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

        $itemKey = strtolower(trim($validated['item_code']) . '|' . trim($validated['item_name']));
        
        // Check duplicate
        $exists = ItemOutstanding::where('item_code', $validated['item_code'])
                                 ->where('item_name', $validated['item_name'])
                                 ->exists();

        $itemOutstanding = ItemOutstanding::create([
            'request_date' => now()->format('Y-m-d'),
            'item_code' => $validated['item_code'],
            'item_name' => $validated['item_name'],
            'user' => $validated['user'] ?? '',
            'outstanding' => $validated['outstanding'] ?? 0,
            'sudah_pp' => $validated['sudah_pp'] ?? 0,
            'outstanding_pp' => $validated['outstanding_pp'] ?? '',
            'ending_balance' => $validated['ending_balance'] ?? 0,
            'maximal_stock' => $validated['maximal_stock'] ?? 0,
            'order_point' => $validated['order_point'] ?? 0,
            'minimal_stock' => $validated['minimal_stock'] ?? 0,
            'note' => null,
            'imported_at' => now(),
            'duplicate_note' => $exists ? 'Item ini sudah ada di list' : null,
        ]);

        $message = $exists
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

        DB::beginTransaction();
        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header
            array_shift($rows);

            $today = now()->format('Y-m-d');
            $imported = 0;
            $updated = 0;

            // Get all current outstandings mapped by item_code|item_name
            // We need to calculate sum of outstanding per item to match Excel "Total Outstanding" logic
            $currentOutstandingSums = ItemOutstanding::selectRaw("item_code, item_name, SUM(outstanding) as total")
                ->groupBy('item_code', 'item_name')
                ->get()
                ->mapWithKeys(function ($item) {
                     $key = strtolower(trim($item->item_code) . '|' . trim($item->item_name));
                     return [$key => (int)$item->total];
                });

            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;

                $itemCode = trim($row[0] ?? '');
                $itemName = trim($row[1] ?? '');
                $excelOutstanding = (int) ($row[2] ?? 0);
                $endingBalance = $row[3] ?? 0;
                $maximalStock = $row[4] ?? 0;
                $orderPoint = $row[5] ?? 0;
                $minimalStock = $row[6] ?? 0;
                $user = trim($row[7] ?? '');
                $outstandingPp = trim($row[8] ?? '');

                if (empty($itemCode) || empty($itemName)) continue;

                $itemKey = strtolower($itemCode . '|' . $itemName);
                $currentTotal = $currentOutstandingSums[$itemKey] ?? 0;
                
                $outstandingDifference = $excelOutstanding - $currentTotal;

                // Find existing items (LIFO or FIFO? Original code iterated session which was LIFO-ish)
                // We'll update the *latest* request or create new
                $latestRequest = ItemOutstanding::where('item_code', $itemCode)
                                                ->where('item_name', $itemName)
                                                ->orderBy('created_at', 'desc')
                                                ->first();

                if ($latestRequest) {
                    $currentRequestOutstanding = $latestRequest->outstanding;
                    $newOutstanding = max(0, $currentRequestOutstanding + $outstandingDifference);
                    
                    $latestRequest->outstanding = $newOutstanding;
                    $latestRequest->outstanding_pp = $outstandingPp;
                    $latestRequest->ending_balance = (int)($endingBalance ?: 0);
                    $latestRequest->maximal_stock = (int)($maximalStock ?: 0);
                    $latestRequest->order_point = (int)($orderPoint ?: 0);
                    $latestRequest->minimal_stock = (int)($minimalStock ?: 0);
                    $latestRequest->user = $user;
                    $latestRequest->imported_at = now();
                    $latestRequest->save();
                    
                    // Update our running sum map so next row (if duplicates in excel? unlikely) is correct
                    // Actually, if multiple rows for same item in Excel, logic might be weird.
                    // Assuming Excel has unique items.
                    $currentOutstandingSums[$itemKey] = ($currentOutstandingSums[$itemKey] ?? 0) + $outstandingDifference;
                    
                    $updated++;
                } else {
                    // Create new
                    ItemOutstanding::create([
                         'request_date' => $today,
                         'item_code' => $itemCode,
                         'item_name' => $itemName,
                         'user' => $user,
                         'outstanding' => $excelOutstanding,
                         'outstanding_pp' => $outstandingPp,
                         'ending_balance' => (int)($endingBalance ?: 0),
                         'maximal_stock' => (int)($maximalStock ?: 0),
                         'order_point' => (int)($orderPoint ?: 0),
                         'minimal_stock' => (int)($minimalStock ?: 0),
                         'imported_at' => now(),
                    ]);
                    $currentOutstandingSums[$itemKey] = $excelOutstanding;
                    $imported++;
                }
            }

            // Cleanup 0 outstanding
            ItemOutstanding::where('outstanding', '<=', 0)->delete();

            DB::commit();

            $message = "Import berhasil! {$imported} item baru ditambahkan.";
            if ($updated > 0) {
                $message .= " {$updated} item duplicate diperbarui (outstanding, outstanding pp).";
            }

            return redirect()->route('item_outstanding.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Item Outstanding Error: ' . $e->getMessage());
            return redirect()->route('item_outstanding.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Update note for a request.
     */
    public function updateNote(Request $request, $id)
    {
        $this->checkMasterAccess();
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->note = $validated['note'];
            $item->save();
        }

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
        $this->checkMasterAccess();
        $validated = $request->validate([
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->sudah_follow = $validated['sudah_follow'];
            $item->sudah_follow_edited_at = now();
            $item->save();
            
            // Sync to Master
            // Note: existing logic tried to sync even if item not found in warehouse_requests by ID.
            // But here ID is DB ID. So finding by ID is reliable.
            // We just need to find corresponding ItemMaster by code/name.
            $this->syncToMaster($item, [
                'sudah_follow' => $item->sudah_follow,
                'sudah_follow_edited_at' => $item->sudah_follow_edited_at
            ]);
        }

        $formattedDate = strtolower(now()->format('M d, H:i'));

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
        $this->checkMasterAccess();
        $validated = $request->validate([
            'pengiriman_tanggal' => 'nullable|date',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->pengiriman_tanggal = $validated['pengiriman_tanggal'];
            $item->pengiriman_tanggal_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'pengiriman_tanggal' => $item->pengiriman_tanggal,
                'pengiriman_tanggal_edited_at' => $item->pengiriman_tanggal_edited_at
            ]);
        }

        $formattedDate = strtolower(now()->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'PENGIRIMAN TANGGAL berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update request WHC for a request.
     */
    public function updateRequestWhc(Request $request, $id)
    {
        // Allow only master or whc to update Request WHC (purchasing read-only)
        if (!auth()->check() || !in_array(auth()->user()->username, ['master', 'whc'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya user master atau whc yang dapat mengisi Request WHC.'
            ], 403);
        }
        $validated = $request->validate([
            'request_whc' => 'nullable|integer|min:0',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->request_whc = $validated['request_whc'];
            $item->request_whc_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'request_whc' => $item->request_whc,
                'request_whc_edited_at' => $item->request_whc_edited_at
            ]);
        } else {
            // Fallback when the ID refers to ItemMaster (requests coming from Item Minim)
            $masterItem = ItemMaster::find($id);
            if (!$masterItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ], 404);
            }

            $masterItem->request_whc = $validated['request_whc'];
            $masterItem->request_whc_edited_at = now();
            $masterItem->save();
        }
        // Is it possible ID refers to MasterItem directly if not found in Outstanding?
        // Original code checked 'warehouse_requests' first, then 'data_master_items'.
        // This implies the ID passed from frontend could be either a request ID OR a master item ID?
        // If the view lists items from both... but index() only lists warehouse_requests.
        // So the ID should be from ItemOutstanding.
        // However, if the UI allows editing master items from another view that hits this controller...
        // Assuming strictly ItemOutstanding context here.

        $formattedDate = strtolower(now()->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'Request WHC berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update follow up (qty, pengiriman tanggal, sudah follow) for a request.
     */
    public function updateFollowUp(Request $request, $id)
    {
        $this->checkMasterAccess();
        $validated = $request->validate([
            'qty_akan_dikirim' => 'nullable|integer|min:0',
            'pengiriman_tanggal' => 'nullable|date',
            'selected_po_no' => 'nullable|string|max:255',
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->qty_akan_dikirim = $validated['qty_akan_dikirim'];
            $item->pengiriman_tanggal = $validated['pengiriman_tanggal'];
            $item->selected_po_no = $validated['selected_po_no'];
            $item->sudah_follow = $validated['sudah_follow'] ?? 'YES';
            $item->sudah_follow_edited_at = now();
            $item->pengiriman_tanggal_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'qty_akan_dikirim' => $item->qty_akan_dikirim,
                'pengiriman_tanggal' => $item->pengiriman_tanggal,
                'selected_po_no' => $item->selected_po_no,
                'sudah_follow' => $item->sudah_follow,
                'sudah_follow_edited_at' => $item->sudah_follow_edited_at,
                'pengiriman_tanggal_edited_at' => $item->pengiriman_tanggal_edited_at
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Follow up berhasil diperbarui',
        ]);
    }

    private function syncToMaster($outstandingItem, $data)
    {
        // Find matching Master Item by code and name
        // Use update for efficiency
        ItemMaster::where('item_code', $outstandingItem->item_code)
                  ->where('item_name', $outstandingItem->item_name)
                  ->update($data);
    }
}
