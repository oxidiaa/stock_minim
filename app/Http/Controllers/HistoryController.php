<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\History;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    /**
     * Display a listing of history items.
     */
    public function index()
    {
        // Fetch from database, ordered by arrival_date desc
        // The original code also filtered by year >= 2000. We can add that if needed, 
        // but typically database constraints or validity is enough. 
        // We will replicate the sort order.
        
        $historyItems = History::orderBy('arrival_date', 'desc')
                               ->orderBy('created_at', 'desc')
                               ->get();

        // Pass to view
        return view('pages.history', compact('historyItems'));
    }

    /**
     * Update a history item.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'scheduled_receipt_qty' => 'nullable|integer|min:0',
            'po_no' => 'nullable|string|max:255',
            'jumlah_item_datang' => 'required|integer|min:0',
            'arrival_date' => 'required|date',
            'pengiriman_tanggal' => 'nullable|date',
        ]);

        try {
            $history = History::findOrFail($id);
            
            $history->update([
                'item_code' => $validated['item_code'],
                'item_name' => $validated['item_name'],
                'supplier_name' => $validated['supplier_name'] ?? '',
                'scheduled_receipt_qty' => $validated['scheduled_receipt_qty'] ?? 0,
                'po_no' => $validated['po_no'] ?? '',
                'jumlah_item_datang' => $validated['jumlah_item_datang'],
                'arrival_date' => $validated['arrival_date'],
                'pengiriman_tanggal' => $validated['pengiriman_tanggal'] ?? null,
                // edited_at is handled by timestamps or we can add a column if schema has it?
                // Migration had timestamps. If strict tracking needed, we can use a field, 
                // but standard updated_at is sufficient.
            ]);
            
            // Sync with session summaries if they exist (for UX consistency with previous implementation)
            $this->syncSessionSummaries($id, $validated);

            $redirectTo = $request->input('redirect_to');
            $message = 'Data history berhasil diperbarui.';

            if ($redirectTo === 'kedatangan_barang') {
                return redirect()->route('kedatangan_barang.index')->with('success', $message);
            }

            return redirect()->route('history.index')->with('success', $message);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating history: ' . $e->getMessage());
        }
    }

    /**
     * Delete a history item.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $history = History::findOrFail($id);
            $history->delete();

            // Sync with session summaries
            $this->startSessionRemoval($id);

            $redirectTo = $request->input('redirect_to');
            $message = 'Item history berhasil dihapus.';

            if ($redirectTo === 'kedatangan_barang') {
                return redirect()->route('kedatangan_barang.index')->with('success', $message);
            }

            return redirect()->route('history.index')->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting history: ' . $e->getMessage());
        }
    }

    /**
     * Helper to sync updates to session summaries (Legacy support)
     */
    private function syncSessionSummaries($id, $data)
    {
        // Update processing import summary if exists
        $importSummary = Session::get('processing_import_summary', ['items' => [], 'item_count' => 0]);

        if (!empty($importSummary['items'])) {
            foreach ($importSummary['items'] as &$summaryItem) {
                if (($summaryItem['history_id'] ?? '') == $id) {
                    $summaryItem['item_code']   = $data['item_code'];
                    $summaryItem['item_name']   = $data['item_name'];
                    $summaryItem['arrived_qty'] = $data['jumlah_item_datang'];
                    $summaryItem['arrival_date']= $data['arrival_date'];
                    break;
                }
            }
            unset($summaryItem);
            Session::put('processing_import_summary', $importSummary);
        }

        // Update kedatangan import summary if exists
        $kedatanganSummary = Session::get('kedatangan_import_summary', ['items' => [], 'item_count' => 0]);

        if (!empty($kedatanganSummary['items'])) {
            foreach ($kedatanganSummary['items'] as &$summaryItem) {
                if (($summaryItem['history_id'] ?? '') == $id) {
                    $summaryItem['item_code']   = $data['item_code'];
                    $summaryItem['item_name']   = $data['item_name'];
                    $summaryItem['supplier_name'] = $data['supplier_name'] ?? '';
                    $summaryItem['scheduled_receipt_qty'] = $data['scheduled_receipt_qty'] ?? 0;
                    $summaryItem['po_no']       = $data['po_no'] ?? '';
                    $summaryItem['arrived_qty'] = $data['jumlah_item_datang'];
                    $summaryItem['arrival_date']= $data['arrival_date'];
                    break;
                }
            }
            unset($summaryItem);
            Session::put('kedatangan_import_summary', $kedatanganSummary);
        }
    }

    /**
     * Helper to remove items from session summaries
     */
    private function startSessionRemoval($id)
    {
        // Update processing import summary if exists
        $importSummary = Session::get('processing_import_summary');
        if (!empty($importSummary['items'])) {
            $importSummary['items'] = array_values(array_filter($importSummary['items'], function ($summaryItem) use ($id) {
                return ($summaryItem['history_id'] ?? '') != $id;
            }));
            $importSummary['item_count'] = count($importSummary['items']);
            Session::put('processing_import_summary', $importSummary);
        }

        // Update kedatangan import summary if exists
        $kedatanganSummary = Session::get('kedatangan_import_summary');
        if (!empty($kedatanganSummary['items'])) {
            $kedatanganSummary['items'] = array_values(array_filter($kedatanganSummary['items'], function ($summaryItem) use ($id) {
                return ($summaryItem['history_id'] ?? '') != $id;
            }));
            $kedatanganSummary['item_count'] = count($kedatanganSummary['items']);
            Session::put('kedatangan_import_summary', $kedatanganSummary);
        }
    }
}
