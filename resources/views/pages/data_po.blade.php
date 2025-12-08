@extends('layouts.app')

@section('title', 'Data PO - Daftar Purchase Order')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">Data PO</h4>
                    <div class="d-flex gap-2">

                    @if(auth()->check() && in_array(auth()->user()->username, ['master', 'whc']))
                        <form action="{{ route('data_po.importExcel') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                            @csrf
                            <input type="file" name="excel_file" accept=".xlsx,.xls" id="excel_file" style="display: none;" onchange="this.form.submit()" required>
                            <button type="button" class="btn btn-success" onclick="document.getElementById('excel_file').click()">
                                <i data-feather="upload"></i> Import Excel
                            </button>
                        </form>
                    @endif

                    @if(auth()->check() && in_array(auth()->user()->username, ['master', 'whc']))
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                            <i data-feather="trash-2"></i> Hapus Semua Item
                        </button>
                    @endif
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Search Box -->
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i data-feather="search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="searchDescription" 
                                       placeholder="Cari berdasarkan Item name...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-striped table-bordered" id="dataTable">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 10; background-color: #212529;">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Item CD</th>
                                <th>Item name</th>
                                <th>Supplier name</th>
                                <th>Sched. receipt qty.</th>
                                <th>PO No.</th>
                                <th>Import</th>
                                <th style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($poItems as $index => $item)
                                <tr data-item-id="{{ $item['id'] }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item['item_code'] ?? '-' }}</td>
                                    <td>{{ $item['item_name'] ?? '-' }}</td>
                                    <td>{{ $item['supplier_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item['scheduled_receipt_qty'] ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $item['po_no'] ?? '-' }}</td>
                                    <td>
                                        @if(!empty($item['imported_at']))
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($item['imported_at'])->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                    @if(auth()->check() && in_array(auth()->user()->username, ['master', 'whc']))
                                        <form action="{{ route('data_po.destroy', $item['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus item ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i data-feather="trash-2" class="icon-sm"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada data PO</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete All Items Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAllModalLabel">
                    <i data-feather="trash-2"></i> Hapus Semua Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data_po.deleteAll') }}" method="POST" id="deleteAllForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading"><i data-feather="alert-triangle"></i> Peringatan!</h6>
                        <p class="mb-0">
                            <strong>Fitur ini akan menghapus SEMUA data PO.</strong>
                        </p>
                        <p class="mb-0 mt-2">
                            <strong>Pastikan Anda sudah membackup data penting sebelum melanjutkan!</strong>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteAllBtn">
                        <i data-feather="trash-2"></i> Ya, Hapus Semua Data PO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .icon-sm { width: 14px; height: 14px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') { feather.replace(); }

    // Search functionality for Item name column
    const searchDescriptionInput = document.getElementById('searchDescription');
    const table = document.getElementById('dataTable');

    function applyFilters() {
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const searchValue = searchDescriptionInput?.value.toLowerCase().trim() || '';
        let rowNum = 1;

        tbody.querySelectorAll('tr').forEach(row => {
            // Search in Item name column (index 2: 0=No, 1=Item CD, 2=Item name)
            const itemNameCell = row.cells[2];
            const itemNameMatch = !searchValue || 
                (itemNameCell && itemNameCell.textContent.toLowerCase().includes(searchValue));

            // Show row if filter matches
            if (itemNameMatch) {
                row.style.display = '';
                // Update row number
                const noCell = row.cells[0];
                if (noCell) {
                    noCell.textContent = rowNum++;
                }
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Add event listener
    searchDescriptionInput?.addEventListener('input', function() {
        applyFilters();
    });

    // Delete All Items Modal functionality
    const deleteAllModal = document.getElementById('deleteAllModal');
    const deleteAllForm = document.getElementById('deleteAllForm');
    const confirmDeleteAllBtn = document.getElementById('confirmDeleteAllBtn');
    
    if (deleteAllModal) {
        deleteAllModal.addEventListener('show.bs.modal', function() {
            if (typeof feather !== 'undefined') { feather.replace(); }
        });

        deleteAllForm.addEventListener('submit', function(e) {
            // Show loading state
            confirmDeleteAllBtn.disabled = true;
            confirmDeleteAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';
        });
    }

    // Initialize feather icons
    if (typeof feather !== 'undefined') { 
        feather.replace(); 
        setTimeout(() => feather.replace(), 100);
    }
});
</script>
@endsection

