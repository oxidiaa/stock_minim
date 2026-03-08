@extends('layouts.app')

@section('title', 'History - Riwayat Kedatangan Barang')

@section('content')
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">History Kedatangan Barang</h4>

                        <div class="d-flex gap-2">
                            @if(auth()->check() && auth()->user()->username !== 'guest' && in_array(auth()->user()->username, ['master', 'whc']))
                                <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                                    <i data-feather="trash-2" class="me-2" style="width: 16px; height: 16px;"></i>
                                    Hapus Terpilih
                                </button>
                            @endif
                            <a href="{{ route('history.export') }}" class="btn btn-success" id="exportBtn">
                                <i data-feather="download" class="me-2" style="width: 16px; height: 16px;"></i>
                                Download Excel
                            </a>
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
                                    <input type="text" class="form-control" id="searchDescription"
                                        placeholder="Cari berdasarkan Item Name...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text" id="calendarIcon" style="cursor:pointer">
                                        <i data-feather="calendar"></i>
                                    </span>

                                    <input type="date" class="form-control" id="filterTanggalKedatangan">

                                    <button class="btn btn-outline-secondary" id="resetTanggal">
                                        Semua
                                    </button>
                                </div>
                            </div>



                        </div>
                    </div>

                    <form id="bulkDeleteForm" action="{{ route('history.bulkDestroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        @if(auth()->check() && auth()->user()->username !== 'guest' && in_array(auth()->user()->username, ['master', 'whc']))
                                            <th style="width: 40px; text-align: center;">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </th>
                                        @endif
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Supplier name</th>
                                        <th>Request WHC</th>
                                        <th>Request Date WHC</th>
                                        <th>PO No.</th>
                                        <th>Jumlah Item yang Datang</th>
                                        <th>Tanggal Kedatangan</th>
                                        <th>Pengiriman Tanggal</th>
                                        @if(auth()->check() && in_array(auth()->user()->username, ['master', 'whc']))
                                            <th>Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($historyItems as $index => $item)
                                        @php
                                            $modalId = 'editHistoryModal' . $index;
                                        @endphp
                                        <tr>
                                            @if(auth()->check() && auth()->user()->username !== 'guest' && in_array(auth()->user()->username, ['master', 'whc']))
                                                <td style="text-align: center;">
                                                    <input class="form-check-input item-checkbox" type="checkbox" name="ids[]"
                                                        value="{{ $item['id'] }}">
                                                </td>
                                            @endif
                                            <td>{{ $item['item_code'] ?? '-' }}</td>
                                            <td class="item-name-cell">{{ $item['item_name'] ?? '-' }}</td>
                                            <td>{{ $item['supplier_name'] ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($item['request_whc'] ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                @if(!empty($item['request_whc_date']))
                                                    {{ \Carbon\Carbon::parse($item['request_whc_date'])->format('d/m/Y') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($item['po_no']) && !empty($item['po_no']))
                                                    <span class="badge bg-info">{{ $item['po_no'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                {{ number_format($item['jumlah_item_datang'] ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="arrival-date-cell">
                                                <strong>{{ date('d/m/Y', strtotime($item['arrival_date'] ?? now())) }}</strong>
                                                @if(!empty($item['edited_at']))
                                                    <br>
                                                    <small class="text-warning">
                                                        <i data-feather="edit-2"
                                                            style="width: 12px; height: 12px; vertical-align: middle;"></i>
                                                        Data telah di edit pada
                                                        {{ \Carbon\Carbon::parse($item['edited_at'])->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                                        WIB
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
                                            @if(auth()->check() && auth()->user()->username !== 'guest' && in_array(auth()->user()->username, ['master', 'whc']))
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <button type="button" class="btn btn-sm btn-warning edit-history-btn"
                                                            data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
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
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Yakin ingin menghapus item ini dari history?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                                <i data-feather="trash-2" class="icon-sm"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
                                            aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="{{ $modalId }}Label">Edit Item History</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('history.update', $item['id'] ?? '') }}"
                                                        method="POST" class="edit-history-form"
                                                        data-item-id="{{ $item['id'] }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Item Code</label>
                                                                <input type="text" class="form-control edit-item-code"
                                                                    name="item_code" value="{{ $item['item_code'] ?? '' }}"
                                                                    required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Item Name</label>
                                                                <input type="text" class="form-control edit-item-name"
                                                                    name="item_name" value="{{ $item['item_name'] ?? '' }}"
                                                                    required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Supplier name</label>
                                                                <input type="text" class="form-control edit-supplier-name"
                                                                    name="supplier_name"
                                                                    value="{{ $item['supplier_name'] ?? '' }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Sched. receipt qty.</label>
                                                                <input type="number"
                                                                    class="form-control edit-scheduled-receipt-qty"
                                                                    name="scheduled_receipt_qty"
                                                                    value="{{ $item['scheduled_receipt_qty'] ?? 0 }}" min="0">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">PO No.</label>
                                                                <input type="text" class="form-control edit-po-no" name="po_no"
                                                                    value="{{ $item['po_no'] ?? '' }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Jumlah Item yang Datang</label>
                                                                <input type="number"
                                                                    class="form-control edit-jumlah-item-datang"
                                                                    name="jumlah_item_datang"
                                                                    value="{{ $item['jumlah_item_datang'] ?? 0 }}" min="0"
                                                                    required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Pengiriman Tanggal</label>
                                                                <input type="date" class="form-control edit-pengiriman-tanggal"
                                                                    name="pengiriman_tanggal"
                                                                    value="{{ isset($item['pengiriman_tanggal']) ? date('Y-m-d', strtotime($item['pengiriman_tanggal'])) : '' }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Tanggal Kedatangan</label>
                                                                <input type="date" class="form-control edit-arrival-date"
                                                                    name="arrival_date"
                                                                    value="{{ isset($item['arrival_date']) ? date('Y-m-d', strtotime($item['arrival_date'])) : date('Y-m-d') }}"
                                                                    required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Belum ada riwayat kedatangan barang
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .icon-sm {
            width: 14px;
            height: 14px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* =======================
               INIT ICON
            ======================= */
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            /* =======================
               ELEMENT
            ======================= */
            const searchInput = document.getElementById('searchDescription');
            const dateInput = document.getElementById('filterTanggalKedatangan');
            const resetBtn = document.getElementById('resetTanggal');
            const calendarIcon = document.getElementById('calendarIcon');
            const exportBtn = document.getElementById('exportBtn');
            const table = document.querySelector('.table-responsive table');

            if (!table) return;

            /* =======================
               FILTER FUNCTION (SATU PINTU)
            ======================= */
            function applyFilters() {
                const searchValue = searchInput.value.toLowerCase().trim();
                const dateValue = dateInput.value;

                let selectedDate = '';
                if (dateValue) {
                    const [y, m, d] = dateValue.split('-');
                    selectedDate = `${d}/${m}/${y}`; // dd/mm/yyyy
                }

                table.querySelectorAll('tbody tr').forEach(row => {

                    /* FILTER ITEM NAME (ganti cells[1] karena struktur index bisa rubah dgn checkbox) */
                    const itemNameCell = row.querySelector('.item-name-cell');
                    const itemName = itemNameCell ? itemNameCell.textContent.toLowerCase() : '';
                    const matchItem = !searchValue || itemName.includes(searchValue);

                    /* FILTER TANGGAL KEDATANGAN (ganti cells[7] karena struktur index bisa rubah dgn checkbox) */
                    let matchDate = true;
                    if (selectedDate) {
                        const arrivalDateCell = row.querySelector('.arrival-date-cell');
                        const text = arrivalDateCell ? arrivalDateCell.textContent : '';
                        const match = text.match(/(\d{2}\/\d{2}\/\d{4})/);
                        matchDate = match && match[1] === selectedDate;
                    }

                    row.style.display = (matchItem && matchDate) ? '' : 'none';
                });

                updateExportUrl(selectedDate);
            }

            /* =======================
               BULK DELETE LOGIC
            ======================= */
            const selectAllCheckbox = document.getElementById('selectAll');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const bulkDeleteForm = document.getElementById('bulkDeleteForm');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
                    itemCheckboxes.forEach(cb => {
                        // Only act on visible rows
                        const row = cb.closest('tr');
                        if (row.style.display !== 'none') {
                            cb.checked = this.checked;
                        }
                    });
                    updateBulkDeleteBtnState();
                });
            }

            itemCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkDeleteBtnState);
            });

            function updateBulkDeleteBtnState() {
                if (!bulkDeleteBtn) return;
                const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
                bulkDeleteBtn.disabled = checkedCount === 0;
                bulkDeleteBtn.innerHTML = `<i data-feather="trash-2" class="me-2" style="width: 16px; height: 16px;"></i> Hapus Terpilih (${checkedCount})`;
                if (typeof feather !== 'undefined') { feather.replace(); }
            }

            if (bulkDeleteBtn && bulkDeleteForm) {
                bulkDeleteBtn.addEventListener('click', function () {
                    if (confirm('Yakin ingin menghapus item yang terpilih?')) {
                        bulkDeleteForm.submit();
                    }
                });
            }

            /* =======================
               EXPORT URL
            ======================= */
            function updateExportUrl(date) {
                const baseUrl = "{{ route('history.export') }}";
                exportBtn.href = date ? `${baseUrl}?arrival_date=${date}` : baseUrl;
            }

            /* =======================
               EVENT LISTENER
            ======================= */
            searchInput.addEventListener('input', applyFilters);
            dateInput.addEventListener('change', applyFilters);

            resetBtn.addEventListener('click', function () {
                dateInput.value = '';
                applyFilters();
            });

            calendarIcon.addEventListener('click', function () {
                dateInput.showPicker
                    ? dateInput.showPicker()
                    : dateInput.focus();
            });

        });
    </script>

@endsection