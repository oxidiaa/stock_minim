@extends('layouts.app')

@section('title', 'Data Master - Daftar Item')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">Data Master</h4>
                    <div class="d-flex gap-2">
                        <form action="{{ route('item_master.importExcel') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                            @csrf
                            <input type="file" name="excel_file" accept=".xlsx,.xls" id="excel_file" style="display: none;" onchange="this.form.submit()" required>
                            <button type="button" class="btn btn-success" onclick="document.getElementById('excel_file').click()">
                                <i data-feather="upload"></i> Import Excel
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                            <i data-feather="trash-2"></i> Hapus Semua Item
                        </button>
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
                                       placeholder="Cari berdasarkan Description...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-striped table-bordered" id="dataTable">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 10; background-color: #212529;">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th>OUTSTANDING</th>
                                <th>ENDING BALANCE</th>
                                <th>MAX</th>
                                <th>ORDER POINT</th>
                                <th>MIN</th>
                                <th>
                                    <div class="filter-header">
                                        <span>User</span>
                                        <select class="form-select form-select-sm filter-select" data-column="8" style="margin-top: 5px;">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </th>
                                <th>Outstanding PP</th>
                                <th>Import</th>
                                <th>Note</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($masterItems as $index => $item)
                                <tr data-item-id="{{ $item['id'] }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item['item_code'] ?? '-' }}</td>
                                    <td>{{ $item['item_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item['outstanding'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item['ending_balance'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item['maximal_stock'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item['order_point'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item['minimal_stock'] ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $item['user'] ?? '-' }}</td>
                                    <td>{{ $item['outstanding_pp'] ?? '-' }}</td>
                                    <td>
                                        @if(!empty($item['imported_at']))
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($item['imported_at'])->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="note-cell" data-id="{{ $item['id'] }}">
                                            <div class="note-display">
                                                <span class="note-text">{{ !empty($item['note']) ? $item['note'] : '-' }}</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 edit-note-btn" title="Edit Note">
                                                    <i data-feather="edit-2" class="icon-sm"></i>
                                                </button>
                                            </div>
                                            <div class="note-edit d-none">
                                                <textarea class="form-control form-control-sm note-input" rows="2" maxlength="500" placeholder="Tulis note...">{{ $item['note'] ?? '' }}</textarea>
                                                <div class="d-flex gap-1 mt-1">
                                                    <button type="button" class="btn btn-sm btn-primary btn-save-note">Simpan</button>
                                                    <button type="button" class="btn btn-sm btn-secondary btn-cancel-note">Batal</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-warning edit-item-btn" 
                                                    data-id="{{ $item['id'] }}"
                                                    data-item-code="{{ $item['item_code'] ?? '' }}"
                                                    data-item-name="{{ $item['item_name'] ?? '' }}"
                                                    data-outstanding="{{ $item['outstanding'] ?? 0 }}"
                                                    data-ending-balance="{{ $item['ending_balance'] ?? 0 }}"
                                                    data-maximal-stock="{{ $item['maximal_stock'] ?? 0 }}"
                                                    data-order-point="{{ $item['order_point'] ?? 0 }}"
                                                    data-minimal-stock="{{ $item['minimal_stock'] ?? 0 }}"
                                                    data-user="{{ $item['user'] ?? '' }}"
                                                    data-outstanding-pp="{{ $item['outstanding_pp'] ?? '' }}"
                                                    title="Edit">
                                                <i data-feather="edit" class="icon-sm"></i>
                                            </button>
                                            <form action="{{ route('item_master.destroy', $item['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus item ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i data-feather="trash-2" class="icon-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted">Belum ada data master item</td>
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
            <form action="{{ route('item_master.deleteAllItems') }}" method="POST" id="deleteAllForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading"><i data-feather="alert-triangle"></i> Peringatan!</h6>
                        <p class="mb-0">
                            <strong>Fitur ini akan menghapus SEMUA data dari halaman yang Anda pilih.</strong>
                        </p>
                        <p class="mb-0 mt-2">
                            <strong>Pastikan Anda sudah membackup data penting sebelum melanjutkan!</strong>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Halaman yang Akan Dihapus:</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="pages[]" value="data_master" id="page_data_master">
                            <label class="form-check-label" for="page_data_master">
                                <strong>Data Master</strong> - Semua item di halaman Data Master
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="pages[]" value="item_outstanding" id="page_item_outstanding">
                            <label class="form-check-label" for="page_item_outstanding">
                                <strong>Item Outstanding</strong> - Semua item di halaman Item Outstanding
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="pages[]" value="history" id="page_history">
                            <label class="form-check-label" for="page_history">
                                <strong>History</strong> - Semua item di halaman History
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="pages[]" value="import_summary" id="page_import_summary">
                            <label class="form-check-label" for="page_import_summary">
                                <strong>Import Summary</strong> - Semua summary import (Processing & Kedatangan Barang)
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="pages[]" value="data_po" id="page_data_po">
                            <label class="form-check-label" for="page_data_po">
                                <strong>Data PO</strong> - Semua item di halaman Data PO
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info" role="alert">
                        <small><i data-feather="info"></i> Centang minimal satu halaman untuk melanjutkan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteAllBtn">
                        <i data-feather="trash-2"></i> Ya, Hapus Data Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editItemForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="item_code" id="edit_item_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="item_name" id="edit_item_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">OUTSTANDING <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="outstanding" id="edit_outstanding" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ENDING BALANCE <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="ending_balance" id="edit_ending_balance" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">MAX <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="maximal_stock" id="edit_maximal_stock" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ORDER POINT <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="order_point" id="edit_order_point" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">MIN <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="minimal_stock" id="edit_minimal_stock" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">User <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="user" id="edit_user" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Outstanding PP</label>
                            <input type="text" class="form-control" name="outstanding_pp" id="edit_outstanding_pp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .note-cell { min-width: 150px; }
    .note-display { display: flex; align-items: center; gap: 5px; }
    .note-text { flex: 1; min-height: 20px; word-wrap: break-word; }
    .note-edit { width: 100%; }
    .note-input { font-size: 0.875rem; }
    .edit-note-btn { opacity: 0.6; transition: opacity 0.2s; }
    .edit-note-btn:hover { opacity: 1; }
    .table thead th { position: relative; }
    .icon-sm { width: 14px; height: 14px; }
    .filter-header { display: flex; flex-direction: column; }
    .filter-select { min-width: 120px; font-size: 0.75rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') { feather.replace(); }

    // Initialize table filter for User column
    function initTableFilter() {
        const table = document.getElementById('dataTable');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const filterSelect = table.querySelector('.filter-select[data-column="8"]');
        
        if (!filterSelect || !tbody) return;

        // Get all unique user values from table
        const userValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            const userCell = row.cells[8]; // User column is index 8
            if (userCell) {
                const userValue = userCell.textContent.trim();
                if (userValue && userValue !== '-') {
                    userValues.add(userValue);
                }
            }
        });

        // Populate filter dropdown
        const sortedUsers = Array.from(userValues).sort();
        sortedUsers.forEach(user => {
            const option = document.createElement('option');
            option.value = user;
            option.textContent = user;
            filterSelect.appendChild(option);
        });

        // Add filter event listener
        filterSelect.addEventListener('change', function() {
            applyFilters();
        });
    }

    // Search functionality for Description column
    const searchDescriptionInput = document.getElementById('searchDescription');
    const filterSelect = document.querySelector('.filter-select[data-column="8"]');

    function applyFilters() {
        const table = document.getElementById('dataTable');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const searchValue = searchDescriptionInput?.value.toLowerCase().trim() || '';
        const filterValue = filterSelect?.value.toLowerCase().trim() || '';
        let rowNum = 1;

        tbody.querySelectorAll('tr').forEach(row => {
            // Search in Description column (index 2)
            const descriptionCell = row.cells[2];
            const descriptionMatch = !searchValue || 
                (descriptionCell && descriptionCell.textContent.toLowerCase().includes(searchValue));

            // Filter by User column (index 8)
            const userCell = row.cells[8];
            const userMatch = !filterValue || 
                (userCell && (
                    userCell.textContent.trim().toLowerCase() === filterValue || 
                    (userCell.textContent.trim() === '-' && filterValue === '')
                ));

            // Show row if both filters match
            if (descriptionMatch && userMatch) {
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

    // Add event listeners
    searchDescriptionInput?.addEventListener('input', function() {
        applyFilters();
    });

    // Initialize filter
    initTableFilter();
    
    // Initialize feather icons
    if (typeof feather !== 'undefined') { 
        feather.replace(); 
        setTimeout(() => feather.replace(), 100);
    }

    // Delete All Items Modal functionality
    const deleteAllModal = document.getElementById('deleteAllModal');
    const deleteAllForm = document.getElementById('deleteAllForm');
    const confirmDeleteAllBtn = document.getElementById('confirmDeleteAllBtn');
    
    if (deleteAllModal) {
        deleteAllModal.addEventListener('show.bs.modal', function() {
            if (typeof feather !== 'undefined') { feather.replace(); }
        });

        deleteAllForm.addEventListener('submit', function(e) {
            const checkboxes = deleteAllForm.querySelectorAll('input[name="pages[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Silakan pilih minimal satu halaman untuk dihapus!');
                return false;
            }
            
            // Show loading state
            confirmDeleteAllBtn.disabled = true;
            confirmDeleteAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';
        });
    }

    // Edit item functionality
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('editItemForm');
            const itemId = this.dataset.id;
            form.action = `/item_master/${itemId}`;
            
            document.getElementById('edit_item_code').value = this.dataset.itemCode;
            document.getElementById('edit_item_name').value = this.dataset.itemName;
            document.getElementById('edit_outstanding').value = this.dataset.outstanding;
            document.getElementById('edit_ending_balance').value = this.dataset.endingBalance;
            document.getElementById('edit_maximal_stock').value = this.dataset.maximalStock;
            document.getElementById('edit_order_point').value = this.dataset.orderPoint;
            document.getElementById('edit_minimal_stock').value = this.dataset.minimalStock;
            document.getElementById('edit_user').value = this.dataset.user || '';
            document.getElementById('edit_outstanding_pp').value = this.dataset.outstandingPp || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
            modal.show();
        });
    });

    // Note functionality
    document.querySelectorAll('.edit-note-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cell = this.closest('.note-cell');
            const display = cell.querySelector('.note-display');
            const edit = cell.querySelector('.note-edit');
            display.classList.add('d-none');
            edit.classList.remove('d-none');
            edit.querySelector('.note-input').focus();
        });
    });

    document.querySelectorAll('.btn-cancel-note').forEach(btn => {
        btn.addEventListener('click', function() {
            const cell = this.closest('.note-cell');
            const display = cell.querySelector('.note-display');
            const edit = cell.querySelector('.note-edit');
            const input = edit.querySelector('.note-input');
            const noteText = cell.querySelector('.note-text');
            const originalNote = noteText.textContent.trim() === '-' ? '' : noteText.textContent.trim();
            input.value = originalNote;
            edit.classList.add('d-none');
            display.classList.remove('d-none');
        });
    });

    document.querySelectorAll('.btn-save-note').forEach(btn => {
        btn.addEventListener('click', function() {
            const cell = this.closest('.note-cell');
            const itemId = cell.dataset.id;
            const input = cell.querySelector('.note-input');
            const noteText = cell.querySelector('.note-text');
            const display = cell.querySelector('.note-display');
            const edit = cell.querySelector('.note-edit');
            const note = input.value.trim();

            const saveBtn = this; const originalText = saveBtn.textContent; saveBtn.disabled = true; saveBtn.textContent = 'Menyimpan...';

            fetch(`/item_master/note/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({ note: note })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    noteText.textContent = note || '-';
                    edit.classList.add('d-none');
                    display.classList.remove('d-none');
                    if (typeof feather !== 'undefined') { feather.replace(); }
                } else {
                    alert('Gagal menyimpan note. Silakan coba lagi.');
                }
            })
            .catch(() => alert('Terjadi error saat menyimpan note.'))
            .finally(() => { saveBtn.disabled = false; saveBtn.textContent = originalText; });
        });
    });
});
</script>
@endsection
