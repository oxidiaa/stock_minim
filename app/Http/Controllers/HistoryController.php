<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class HistoryController extends Controller
{
    /**
     * Display a listing of history items.
     */
    public function index()
    {
        $historyItems = Session::get('history_items', []);

        // Filter out items with invalid dates
        $historyItems = array_filter($historyItems, function ($item) {
            if (empty($item['arrival_date'])) {
                return false;
            }
            try {
                $year = (int) date('Y', strtotime($item['arrival_date']));
                return $year >= 2000;
            } catch (\Exception $e) {
                return false;
            }
        });

        $historyItems = array_values($historyItems);

        // Sort by date (newest first)
        usort($historyItems, function ($a, $b) {
            return strtotime($b['arrival_date']) - strtotime($a['arrival_date']);
        });

        Session::put('history_items', $historyItems);

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
            'jumlah_item_datang' => 'required|integer|min:0',
            'arrival_date' => 'required|date',
        ]);

        $historyItems = Session::get('history_items', []);
        $found = false;

        foreach ($historyItems as &$item) {
            if (($item['id'] ?? '') === $id) {
                $item['item_code'] = $validated['item_code'];
                $item['item_name'] = $validated['item_name'];
                $item['jumlah_item_datang'] = $validated['jumlah_item_datang'];
                $item['arrival_date'] = $validated['arrival_date'];
                // Simpan informasi edit dengan timestamp
                $item['edited_at'] = Carbon::now('Asia/Jakarta')->toDateTimeString();
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            return redirect()->route('history.index')->with('error', 'Item history tidak ditemukan.');
        }

        Session::put('history_items', $historyItems);

        // Update processing import summary if exists
        $importSummary = Session::get('processing_import_summary', ['items' => [], 'item_count' => 0]);

        if (!empty($importSummary['items'])) {
            foreach ($importSummary['items'] as &$summaryItem) {
                if (($summaryItem['history_id'] ?? '') === $id) {
                    $summaryItem['item_code']   = $validated['item_code'];
                    $summaryItem['item_name']   = $validated['item_name'];
                    $summaryItem['arrived_qty'] = $validated['jumlah_item_datang'];
                    $summaryItem['arrival_date']= $validated['arrival_date'];
                    break;
                }
            }
            unset($summaryItem); // (opsional) putus reference
            Session::put('processing_import_summary', $importSummary);
        }

        // Update kedatangan import summary if exists
        $kedatanganSummary = Session::get('kedatangan_import_summary', ['items' => [], 'item_count' => 0]);

        if (!empty($kedatanganSummary['items'])) {
            foreach ($kedatanganSummary['items'] as &$summaryItem) {
                if (($summaryItem['history_id'] ?? '') === $id) {
                    $summaryItem['item_code']   = $validated['item_code'];
                    $summaryItem['item_name']   = $validated['item_name'];
                    $summaryItem['arrived_qty'] = $validated['jumlah_item_datang'];
                    $summaryItem['arrival_date']= $validated['arrival_date'];
                    break;
                }
            }
            unset($summaryItem);
            Session::put('kedatangan_import_summary', $kedatanganSummary);
        }

        $redirectTo = $request->input('redirect_to');
        $message = 'Data history berhasil diperbarui.';

        if ($redirectTo === 'kedatangan_barang') {
            return redirect()->route('kedatangan_barang.index')->with('success', $message);
        }

        return redirect()->route('history.index')->with('success', $message);
    }

    /**
     * Delete a history item.
     */
    public function destroy(Request $request, $id)
    {
        $historyItems = Session::get('history_items', []);
        $originalCount = count($historyItems);

        $historyItems = array_filter($historyItems, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        });

        if (count($historyItems) === $originalCount) {
            return redirect()->route('history.index')->with('error', 'Item history tidak ditemukan.');
        }

        Session::put('history_items', array_values($historyItems));

        // Update processing import summary if exists
        $importSummary = Session::get('processing_import_summary');
        if (!empty($importSummary['items'])) {
            $importSummary['items'] = array_values(array_filter($importSummary['items'], function ($summaryItem) use ($id) {
                return ($summaryItem['history_id'] ?? '') !== $id;
            }));
            $importSummary['item_count'] = count($importSummary['items']);
            Session::put('processing_import_summary', $importSummary);
        }

        // Update kedatangan import summary if exists
        $kedatanganSummary = Session::get('kedatangan_import_summary');
        if (!empty($kedatanganSummary['items'])) {
            $kedatanganSummary['items'] = array_values(array_filter($kedatanganSummary['items'], function ($summaryItem) use ($id) {
                return ($summaryItem['history_id'] ?? '') !== $id;
            }));
            $kedatanganSummary['item_count'] = count($kedatanganSummary['items']);
            Session::put('kedatangan_import_summary', $kedatanganSummary);
        }

        $redirectTo = $request->input('redirect_to');
        $message = 'Item history berhasil dihapus.';

        if ($redirectTo === 'kedatangan_barang') {
            return redirect()->route('kedatangan_barang.index')->with('success', $message);
        }

        return redirect()->route('history.index')->with('success', $message);
    }
}

