<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $masterItems = Session::get('data_master_items', []);
        $warehouseRequests = Session::get('warehouse_requests', []);
        $poItems = Session::get('data_po_items', []);
        $historyItems = Session::get('history_items', []);
        $kedatanganBarangItems = Session::get('kedatangan_barang_items', []);

        // Calculate statistics
        $stats = [
            'total_items' => count($masterItems),
            'outstanding_items' => count($warehouseRequests),
            'item_minim' => 0,
            'total_po' => count($poItems),
            'total_history' => count($historyItems),
            'total_kedatangan' => count($kedatanganBarangItems),
            'total_outstanding_qty' => 0,
            'total_ending_balance' => 0,
            'total_po_qty' => 0,
            'total_history_qty' => 0,
            'items_follow_up_yes' => 0,
            'items_follow_up_no' => 0,
            'items_with_po' => 0,
            'items_without_po' => 0,
        ];

        // Calculate item minim (ending_balance < minimal_stock && outstanding > 0)
        $minimItems = [];
        foreach ($masterItems as $item) {
            $endingBalance = (int) ($item['ending_balance'] ?? 0);
            $minimalStock = (int) ($item['minimal_stock'] ?? 0);
            $outstanding = (int) ($item['outstanding'] ?? 0);
            
            $stats['total_ending_balance'] += $endingBalance;
            
            if ($endingBalance < $minimalStock && $outstanding > 0) {
                $stats['item_minim']++;
                
                // Get sudah_follow from masterItems (it's synced from warehouse_requests)
                $sudahFollow = $item['sudah_follow'] ?? '';
                $minimItems[] = $sudahFollow;
            }
        }

        // Calculate follow up status from item minim only
        foreach ($minimItems as $sudahFollow) {
            if ($sudahFollow === 'YES') {
                $stats['items_follow_up_yes']++;
            } else {
                // Count as NO if not YES (including empty/null values)
                $stats['items_follow_up_no']++;
            }
        }

        // Calculate outstanding quantities (from warehouse_requests)
        foreach ($warehouseRequests as $request) {
            $stats['total_outstanding_qty'] += (int) ($request['outstanding'] ?? 0);
            
            // Check if item has PO data
            $itemCode = strtolower(trim($request['item_code'] ?? ''));
            $itemName = strtolower(trim($request['item_name'] ?? ''));
            $hasPO = false;
            
            foreach ($poItems as $poItem) {
                $poItemCode = strtolower(trim($poItem['item_code'] ?? ''));
                $poItemName = strtolower(trim($poItem['item_name'] ?? ''));
                
                if ($itemCode === $poItemCode && $itemName === $poItemName) {
                    $hasPO = true;
                    break;
                }
            }
            
            if ($hasPO) {
                $stats['items_with_po']++;
            } else {
                $stats['items_without_po']++;
            }
        }

        // Calculate PO quantities
        foreach ($poItems as $poItem) {
            $stats['total_po_qty'] += (int) ($poItem['scheduled_receipt_qty'] ?? 0);
        }

        // Calculate history quantities
        foreach ($historyItems as $historyItem) {
            $stats['total_history_qty'] += (int) ($historyItem['jumlah_item_datang'] ?? 0);
        }

        // Get recent items (last 5 warehouse requests)
        $recentRequests = array_slice($warehouseRequests, 0, 5);

        // Get items that need attention (item minim)
        $itemsNeedAttention = [];
        foreach ($masterItems as $item) {
            $endingBalance = (int) ($item['ending_balance'] ?? 0);
            $minimalStock = (int) ($item['minimal_stock'] ?? 0);
            $outstanding = (int) ($item['outstanding'] ?? 0);
            
            if ($endingBalance < $minimalStock && $outstanding > 0) {
                $itemsNeedAttention[] = [
                    'item_code' => $item['item_code'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'ending_balance' => $endingBalance,
                    'minimal_stock' => $minimalStock,
                    'outstanding' => $outstanding,
                ];
            }
        }
        $itemsNeedAttention = array_slice($itemsNeedAttention, 0, 5);

        // Get recent history (last 5)
        $recentHistory = array_slice($historyItems, 0, 5);

        // Calculate chart data (last 7 days history)
        $chartData = [];
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $last7Days[$date] = 0;
        }

        foreach ($historyItems as $historyItem) {
            $arrivalDate = $historyItem['arrival_date'] ?? '';
            if (!empty($arrivalDate)) {
                try {
                    $date = Carbon::parse($arrivalDate)->format('Y-m-d');
                    if (isset($last7Days[$date])) {
                        $last7Days[$date] += (int) ($historyItem['jumlah_item_datang'] ?? 0);
                    }
                } catch (\Exception $e) {
                    // Skip invalid dates
                }
            }
        }

        $chartData = [
            'labels' => array_map(function($date) {
                return Carbon::parse($date)->format('d M');
            }, array_keys($last7Days)),
            'data' => array_values($last7Days),
        ];

        return view('pages.dashboard', compact(
            'stats',
            'recentRequests',
            'itemsNeedAttention',
            'recentHistory',
            'chartData'
        ));
    }
}

