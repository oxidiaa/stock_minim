<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\ItemMaster;
use App\Models\ItemOutstanding;
use App\Models\DataPO;
use App\Models\History;
use App\Models\KedatanganBarang;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Get data from database
        $masterItems = ItemMaster::all();
        $outstandingItems = ItemOutstanding::where('outstanding', '>', 0)->get();
        $poItems = DataPO::all();
        $historyItems = History::orderBy('arrival_date', 'desc')->get();
        $kedatanganBarangItems = KedatanganBarang::all();

        // Calculate statistics
        $stats = [
            'total_items' => $masterItems->count(),
            'outstanding_items' => $outstandingItems->count(),
            'item_minim' => 0,
            'total_po' => $poItems->count(),
            'total_history' => $historyItems->count(),
            'total_kedatangan' => $kedatanganBarangItems->count(),
            'total_outstanding_qty' => $outstandingItems->sum('outstanding'),
            'total_ending_balance' => $masterItems->sum('ending_balance'),
            'total_po_qty' => $poItems->sum('scheduled_receipt_qty'),
            'total_history_qty' => $historyItems->sum('jumlah_item_datang'),
            'items_follow_up_yes' => 0,
            'items_follow_up_no' => 0,
            'items_with_po' => 0,
            'items_without_po' => 0,
        ];

        // Calculate item minim (ending_balance <= order_point && outstanding > 0)
        $minimItems = ItemMaster::whereColumn('ending_balance', '<=', 'order_point')
            ->where('outstanding', '>', 0)
            ->get();

        $stats['item_minim'] = $minimItems->count();

        // Calculate follow up status from item minim
        foreach ($minimItems as $item) {
            $sudahFollow = $item->sudah_follow ?? '';
            if ($sudahFollow === 'YES') {
                $stats['items_follow_up_yes']++;
            } else {
                $stats['items_follow_up_no']++;
            }
        }

        // Calculate items with/without PO
        $itemCodesWithPO = $poItems->pluck('item_code')->unique()->toArray();
        foreach ($outstandingItems as $outstanding) {
            if (in_array($outstanding->item_code, $itemCodesWithPO)) {
                $stats['items_with_po']++;
            } else {
                $stats['items_without_po']++;
            }
        }

        // Get recent outstanding items (last 5)
        $recentRequests = $outstandingItems->take(5)->map(function($item) {
            return [
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'outstanding' => $item->outstanding,
                'sudah_follow' => $item->sudah_follow ?? '',
            ];
        })->toArray();

        // Get items that need attention (item minim) - last 5
        $itemsNeedAttention = $minimItems->take(5)->map(function($item) {
            return [
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'ending_balance' => $item->ending_balance,
                'minimal_stock' => $item->minimal_stock,
                'outstanding' => $item->outstanding,
            ];
        })->toArray();

        // Get recent history (last 5)
        $recentHistory = $historyItems->take(5)->map(function($item) {
            return [
                'arrival_date' => $item->arrival_date,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'jumlah_item_datang' => $item->jumlah_item_datang,
                'po_no' => $item->po_no,
            ];
        })->toArray();

        // Calculate chart data (last 7 days history)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $last7Days[$date] = 0;
        }

        foreach ($historyItems as $historyItem) {
            $arrivalDate = $historyItem->arrival_date;
            if ($arrivalDate) {
                try {
                    $date = Carbon::parse($arrivalDate)->format('Y-m-d');
                    if (isset($last7Days[$date])) {
                        $last7Days[$date] += (int) ($historyItem->jumlah_item_datang ?? 0);
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

