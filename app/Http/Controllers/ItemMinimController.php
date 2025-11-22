<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ItemMinimController extends Controller
{
    /**
     * Display a listing of items where ending_balance < order_point.
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
        
        // Filter items where ending_balance < order_point AND outstanding > 0
        $minimItems = array_filter($masterItems, function ($item) {
            $endingBalance = (int) ($item['ending_balance'] ?? 0);
            $orderPoint = (int) ($item['order_point'] ?? 0);
            $outstanding = (int) ($item['outstanding'] ?? 0);
            return $endingBalance < $orderPoint && $outstanding > 0;
        });

        $minimItems = array_values($minimItems);

        // Sort by item code
        usort($minimItems, function ($a, $b) {
            return strcmp($a['item_code'] ?? '', $b['item_code'] ?? '');
        });

        return view('pages.item_minim', compact('minimItems'));
    }

    /**
     * Update note for a minim item.
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
     * Update a minim item.
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
            'user' => 'nullable|string|max:255',
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
                $item['user'] = $validated['user'] ?? '';
                $item['outstanding_pp'] = $validated['outstanding_pp'] ?? '';
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            return redirect()->route('item_minim.index')->with('error', 'Item tidak ditemukan.');
        }

        Session::put('data_master_items', $masterItems);

        return redirect()->route('item_minim.index')->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Delete a minim item.
     */
    public function destroy($id)
    {
        $masterItems = Session::get('data_master_items', []);
        $originalCount = count($masterItems);

        $masterItems = array_filter($masterItems, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        });

        if (count($masterItems) === $originalCount) {
            return redirect()->route('item_minim.index')->with('error', 'Item tidak ditemukan.');
        }

        Session::put('data_master_items', array_values($masterItems));

        return redirect()->route('item_minim.index')->with('success', 'Item berhasil dihapus.');
    }
}

