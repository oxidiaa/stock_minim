@extends('layouts.app')

@section('title', 'History - Riwayat Kedatangan Barang')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">History Kedatangan Barang</h4>
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
                                       placeholder="Cari berdasarkan Item Name...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i data-feather="calendar"></i>
                                </span>
                                <select class="form-select" id="filterTanggalKedatangan">
                                    <option value="">Semua Tanggal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 10; background-color: #212529;">
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Supplier name</th>
                                <th>Sched. receipt qty.</th>
                                <th>PO No.</th>
                                <th>Jumlah Item yang Datang</th>
                                <th>Tanggal Kedatangan</th>
                                <th>Pengiriman Tanggal</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historyItems as $index => $item)
                                @php
                                    $modalId = 'editHistoryModal' . $index;
                                @endphp
                                <tr>
                                    <td>{{ $item['item_code'] ?? '-' }}</td>
                                    <td>{{ $item['item_name'] ?? '-' }}</td>
                                    <td>{{ $item['supplier_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item['scheduled_receipt_qty'] ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        @if(isset($item['po_no']) && !empty($item['po_no']))
                                            <span class="badge bg-info">{{ $item['po_no'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($item['jumlah_item_datang'] ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        <strong>{{ date('d/m/Y', strtotime($item['arrival_date'] ?? now())) }}</strong>
                                        @if(!empty($item['edited_at']))
                                            <br>
                                            <small class="text-warning">
                                                <i data-feather="edit-2" style="width: 12px; height: 12px; vertical-align: middle;"></i>
                                                Data telah di edit pada {{ \Carbon\Carbon::parse($item['edited_at'])->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($item['pengiriman_tanggal']) && !empty($item['pengiriman_tanggal']))
                                            <strong>{{ \Carbon\Carbon::parse($item['pengiriman_tanggal'])->format('d/m/Y') }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning edit-history-btn" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#{{ $modalId }}"
                                                    data-id="{{ $item['id'] }}"
                                                    data-item-code="{{ $item['item_code'] ?? '' }}"
                                                    data-item-name="{{ $item['item_name'] ?? '' }}"
                                                    data-supplier-name="{{ $item['supplier_name'] ?? '' }}"
                                                    data-scheduled-receipt-qty="{{ $item['scheduled_receipt_qty'] ?? 0 }}"
                                                    data-po-no="{{ $item['po_no'] ?? '' }}"
                                                    data-jumlah-item-datang="{{ $item['jumlah_item_datang'] ?? 0 }}"
                                                    data-arrival-date="{{ isset($item['arrival_date']) ? date('Y-m-d', strtotime($item['arrival_date'])) : date('Y-m-d') }}"
                                                    data-pengiriman-tanggal="{{ isset($item['pengiriman_tanggal']) ? date('Y-m-d', strtotime($item['pengiriman_tanggal'])) : '' }}"
                                                    title="Edit">
                                                <i data-feather="edit" class="icon-sm"></i>
                                            </button>
                                            <form action="{{ route('history.destroy', $item['id'] ?? '') }}" 
                                                  method="POST" 
                                                  class="d-inline" 
                                                  onsubmit="return confirm('Yakin ingin menghapus item ini dari history?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i data-feather="trash-2" class="icon-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="{{ $modalId }}Label">Edit Item History</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('history.update', $item['id'] ?? '') }}" method="POST" class="edit-history-form" data-item-id="{{ $item['id'] }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Item Code</label>
                                                        <input type="text" class="form-control edit-item-code" name="item_code" value="{{ $item['item_code'] ?? '' }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Item Name</label>
                                                        <input type="text" class="form-control edit-item-name" name="item_name" value="{{ $item['item_name'] ?? '' }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Supplier name</label>
                                                        <input type="text" class="form-control edit-supplier-name" name="supplier_name" value="{{ $item['supplier_name'] ?? '' }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Sched. receipt qty.</label>
                                                        <input type="number" class="form-control edit-scheduled-receipt-qty" name="scheduled_receipt_qty" value="{{ $item['scheduled_receipt_qty'] ?? 0 }}" min="0">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">PO No.</label>
                                                        <input type="text" class="form-control edit-po-no" name="po_no" value="{{ $item['po_no'] ?? '' }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jumlah Item yang Datang</label>
                                                        <input type="number" class="form-control edit-jumlah-item-datang" name="jumlah_item_datang" value="{{ $item['jumlah_item_datang'] ?? 0 }}" min="0" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Pengiriman Tanggal</label>
                                                        <input type="date" class="form-control edit-pengiriman-tanggal" name="pengiriman_tanggal" value="{{ isset($item['pengiriman_tanggal']) ? date('Y-m-d', strtotime($item['pengiriman_tanggal'])) : '' }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Tanggal Kedatangan</label>
                                                        <input type="date" class="form-control edit-arrival-date" name="arrival_date" value="{{ isset($item['arrival_date']) ? date('Y-m-d', strtotime($item['arrival_date'])) : date('Y-m-d') }}" required>
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
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada riwayat kedatangan barang</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .icon-sm { width: 14px; height: 14px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize feather icons
    if (typeof feather !== 'undefined') { 
        feather.replace(); 
        setTimeout(() => feather.replace(), 100);
    }

    // Handle edit button click - populate modal with data from button
    document.querySelectorAll('.edit-history-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const modal = document.querySelector(modalId);
            if (modal) {
                const form = modal.querySelector('.edit-history-form');
                if (form) {
                    form.querySelector('.edit-item-code').value = this.getAttribute('data-item-code') || '';
                    form.querySelector('.edit-item-name').value = this.getAttribute('data-item-name') || '';
                    form.querySelector('.edit-supplier-name').value = this.getAttribute('data-supplier-name') || '';
                    form.querySelector('.edit-scheduled-receipt-qty').value = this.getAttribute('data-scheduled-receipt-qty') || 0;
                    form.querySelector('.edit-po-no').value = this.getAttribute('data-po-no') || '';
                    form.querySelector('.edit-jumlah-item-datang').value = this.getAttribute('data-jumlah-item-datang') || 0;
                    form.querySelector('.edit-arrival-date').value = this.getAttribute('data-arrival-date') || '';
                    form.querySelector('.edit-pengiriman-tanggal').value = this.getAttribute('data-pengiriman-tanggal') || '';
                }
            }
            
            setTimeout(() => {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }, 100);
        });
    });

    // Search functionality for Item Name column
    const searchDescriptionInput = document.getElementById('searchDescription');
    const filterTanggalKedatangan = document.getElementById('filterTanggalKedatangan');
    const table = document.querySelector('.table-responsive table');

    // Populate tanggal kedatangan filter
    function populateTanggalKedatanganFilter() {
        if (!table || !filterTanggalKedatangan) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const tanggalValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            // Tanggal Kedatangan is in column index 6
            const tanggalCell = row.cells[6];
            if (tanggalCell) {
                // Get the date part (before any additional text like "Data telah di edit")
                const cellText = tanggalCell.textContent.trim();
                // Extract date part (format: dd/mm/yyyy)
                const dateMatch = cellText.match(/(\d{2}\/\d{2}\/\d{4})/);
                if (dateMatch) {
                    tanggalValues.add(dateMatch[1]);
                }
            }
        });

        // Reset previous dynamic options
        while (filterTanggalKedatangan.options.length > 1) {
            filterTanggalKedatangan.remove(1);
        }

        // Sort dates (convert to date objects for proper sorting)
        const sortedDates = Array.from(tanggalValues).sort((a, b) => {
            // Convert dd/mm/yyyy to date for comparison
            const dateA = a.split('/').reverse().join('-');
            const dateB = b.split('/').reverse().join('-');
            return new Date(dateB) - new Date(dateA); // Sort descending (newest first)
        });

        sortedDates.forEach(tanggal => {
            const option = document.createElement('option');
            option.value = tanggal;
            option.textContent = tanggal;
            filterTanggalKedatangan.appendChild(option);
        });
    }

    // Apply filters
    function applyFilters() {
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const searchValue = searchDescriptionInput?.value.toLowerCase().trim() || '';
        const tanggalFilterValue = filterTanggalKedatangan?.value.trim() || '';

        tbody.querySelectorAll('tr').forEach(row => {
            // Search in Item Name column (index 1)
            const itemNameCell = row.cells[1];
            const itemNameMatch = !searchValue || 
                (itemNameCell && itemNameCell.textContent.toLowerCase().includes(searchValue));

            // Filter by Tanggal Kedatangan (index 6)
            let tanggalMatch = true;
            if (tanggalFilterValue) {
                const tanggalCell = row.cells[6];
                if (tanggalCell) {
                    const cellText = tanggalCell.textContent.trim();
                    // Extract date part (format: dd/mm/yyyy)
                    const dateMatch = cellText.match(/(\d{2}\/\d{2}\/\d{4})/);
                    if (dateMatch) {
                        tanggalMatch = dateMatch[1] === tanggalFilterValue;
                    } else {
                        tanggalMatch = false;
                    }
                } else {
                    tanggalMatch = false;
                }
            }

            // Show row if all filters match
            if (itemNameMatch && tanggalMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Initialize filter
    populateTanggalKedatanganFilter();

    // Add event listeners
    searchDescriptionInput?.addEventListener('input', applyFilters);
    filterTanggalKedatangan?.addEventListener('change', applyFilters);
});
</script>
@endsection

