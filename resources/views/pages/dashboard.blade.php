@extends('layouts.app')

@section('title', 'Dashboard - Stock Management System')

@section('content')
<div class="row">
    <!-- Page Header -->
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Dashboard</h4>
                <p class="text-muted mb-0">Overview sistem manajemen stok</p>
            </div>
            <div>
                <span class="badge bg-primary fs-6 px-3 py-2">
                    <i data-feather="calendar" class="me-2" style="width: 18px; height: 18px;"></i>
                    {{ \Carbon\Carbon::now()->format('d F Y') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2" style="border-left: 4px solid #4e73df !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Items
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['total_items'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="package" class="text-primary" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2" style="border-left: 4px solid #f6c23e !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Item Outstanding
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['outstanding_items'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-muted mt-1">
                            Qty: {{ number_format($stats['total_outstanding_qty'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="alert-circle" class="text-warning" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2" style="border-left: 4px solid #e74a3b !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Item Minim
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['item_minim'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-muted mt-1">
                            Perlu perhatian
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="alert-triangle" class="text-danger" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2" style="border-left: 4px solid #1cc88a !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total PO
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['total_po'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-muted mt-1">
                            Qty: {{ number_format($stats['total_po_qty'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="file-text" class="text-success" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2" style="border-left: 4px solid #36b9cc !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total History
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['total_history'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-muted mt-1">
                            Qty: {{ number_format($stats['total_history_qty'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="clock" class="text-info" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2" style="border-left: 4px solid #858796 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            Ending Balance
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['total_ending_balance'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="trending-up" class="text-secondary" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2" style="border-left: 4px solid #1cc88a !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Follow Up: YES
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['items_follow_up_yes'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="check-circle" class="text-success" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2" style="border-left: 4px solid #f6c23e !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Follow Up: NO
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($stats['items_follow_up_no'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i data-feather="x-circle" class="text-warning" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Chart -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="bar-chart-2" class="me-2" style="width: 18px; height: 18px;"></i>
                    Kedatangan Barang (7 Hari Terakhir)
                </h6>
            </div>
            <div class="card-body">
                <canvas id="arrivalChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- PO Status -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="pie-chart" class="me-2" style="width: 18px; height: 18px;"></i>
                    Status PO
                </h6>
            </div>
            <div class="card-body">
                <canvas id="poStatusChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row">
    <!-- Recent Outstanding Items -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="list" class="me-2" style="width: 18px; height: 18px;"></i>
                    Item Outstanding Terbaru
                </h6>
                <a href="{{ route('item_outstanding.index') }}" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th class="text-end">Outstanding</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $request)
                                <tr>
                                    <td>{{ $request['item_code'] ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($request['item_name'] ?? '-', 30) }}</td>
                                    <td class="text-end">{{ number_format($request['outstanding'] ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        @if(($request['sudah_follow'] ?? '') === 'YES')
                                            <span class="badge bg-success">YES</span>
                                        @elseif(($request['sudah_follow'] ?? '') === 'NO')
                                            <span class="badge bg-warning">NO</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Need Attention -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-danger text-white">
                <h6 class="m-0 font-weight-bold">
                    <i data-feather="alert-triangle" class="me-2" style="width: 18px; height: 18px;"></i>
                    Item Perlu Perhatian
                </h6>
                <a href="{{ route('item_minim.index') }}" class="btn btn-sm btn-light">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th class="text-end">Ending Balance</th>
                                <th class="text-end">Min</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemsNeedAttention as $item)
                                <tr>
                                    <td>{{ $item['item_code'] ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item['item_name'] ?? '-', 30) }}</td>
                                    <td class="text-end text-danger">
                                        <strong>{{ number_format($item['ending_balance'], 0, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-end">{{ number_format($item['minimal_stock'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada item yang perlu perhatian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent History -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="clock" class="me-2" style="width: 18px; height: 18px;"></i>
                    History Kedatangan Barang Terbaru
                </h6>
                <a href="{{ route('history.index') }}" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th class="text-end">Jumlah Datang</th>
                                <th>PO No.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentHistory as $history)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($history['arrival_date'])->format('d/m/Y') }}</td>
                                    <td>{{ $history['item_code'] ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($history['item_name'] ?? '-', 40) }}</td>
                                    <td class="text-end">{{ number_format($history['jumlah_item_datang'] ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        @if(!empty($history['po_no']))
                                            <span class="badge bg-info">{{ $history['po_no'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Tidak ada history</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="zap" class="me-2" style="width: 18px; height: 18px;"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="{{ route('item_outstanding.index') }}" class="btn btn-outline-primary w-100 py-3">
                            <i data-feather="list" class="mb-2" style="width: 24px; height: 24px;"></i>
                            <div>Item Outstanding</div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('item_minim.index') }}" class="btn btn-outline-danger w-100 py-3">
                            <i data-feather="alert-triangle" class="mb-2" style="width: 24px; height: 24px;"></i>
                            <div>Item Minim</div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('data_po.index') }}" class="btn btn-outline-success w-100 py-3">
                            <i data-feather="file-text" class="mb-2" style="width: 24px; height: 24px;"></i>
                            <div>Data PO</div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('kedatangan_barang.index') }}" class="btn btn-outline-info w-100 py-3">
                            <i data-feather="truck" class="mb-2" style="width: 24px; height: 24px;"></i>
                            <div>Kedatangan Barang</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather Icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Arrival Chart
    const arrivalCtx = document.getElementById('arrivalChart');
    if (arrivalCtx) {
        new Chart(arrivalCtx, {
            type: 'line',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: 'Jumlah Item Datang',
                    data: @json($chartData['data']),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    // PO Status Chart
    const poStatusCtx = document.getElementById('poStatusChart');
    if (poStatusCtx) {
        const itemsWithPO = {{ $stats['items_with_po'] }};
        const itemsWithoutPO = {{ $stats['items_without_po'] }};
        
        new Chart(poStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Dengan PO', 'Tanpa PO'],
                datasets: [{
                    data: [itemsWithPO, itemsWithoutPO],
                    backgroundColor: [
                        'rgba(28, 200, 138, 0.8)',
                        'rgba(246, 194, 62, 0.8)'
                    ],
                    borderColor: [
                        'rgba(28, 200, 138, 1)',
                        'rgba(246, 194, 62, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-left-danger {
    border-left: 4px solid #e74a3b !important;
}
.border-left-secondary {
    border-left: 4px solid #858796 !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endsection

