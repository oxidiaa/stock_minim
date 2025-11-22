@extends('layouts.app')

@section('title', 'Item Outstanding - Daftar Request')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">Item Outstanding</h4>
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
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 10; background-color: #212529;">
                            <tr>
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
                                        <select class="form-select form-select-sm filter-select" data-column="7" style="margin-top: 5px;">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </th>
                                <th>Outstanding PP</th>
                                <th>Sched. receipt qty.</th>
                                <th>PO NO.</th>
                                <th>Tanggal Request</th>
                                <th>Import</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $request)
                                @php
                                    $poData = $request['po_data'] ?? [];
                                    $hasMultiplePO = $request['has_multiple_po'] ?? false;
                                @endphp
                                <tr class="{{ !empty($request['duplicate_note'] ?? null) ? 'table-warning' : '' }}"
                                    data-po-data="{{ json_encode($poData) }}">
                                    <td>{{ $request['item_code'] ?? '-' }}</td>
                                    <td>{{ $request['item_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($request['outstanding'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($request['ending_balance'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($request['maximal_stock'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($request['order_point'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($request['minimal_stock'] ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $request['user'] ?? '-' }}</td>
                                    <td>{{ $request['outstanding_pp'] ?? '-' }}</td>
                                    <td class="text-end receipt-qty-cell" 
                                        data-total-qty="{{ $request['total_receipt_qty'] ?? 0 }}">
                                        @if(!empty($poData))
                                            @if($hasMultiplePO && count($poData) > 1)
                                                {{ number_format($poData[0]['total_qty'] ?? 0, 0, ',', '.') }}
                                            @else
                                                {{ number_format($request['total_receipt_qty'] ?? 0, 0, ',', '.') }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($poData))
                                            @if($hasMultiplePO && count($poData) > 1)
                                                <select class="form-select form-select-sm po-select" 
                                                        data-item-id="{{ $request['id'] }}"
                                                        style="min-width: 150px;">
                                                    @foreach($poData as $index => $po)
                                                        <option value="{{ $po['po_no'] }}" 
                                                                data-total-qty="{{ $po['total_qty'] }}"
                                                                data-item-count="{{ count($po['items']) }}"
                                                                {{ $index === 0 ? 'selected' : '' }}>
                                                            {{ $po['po_no'] }} (Qty: {{ number_format($po['total_qty'], 0, ',', '.') }}, {{ count($po['items']) }} item)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                {{ $poData[0]['po_no'] ?? '-' }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td><strong>{{ date('d/m/Y', strtotime($request['request_date'] ?? now())) }}</strong></td>
                                    <td>
                                        @if(!empty($request['imported_at']))
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($request['imported_at'])->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="note-cell" data-id="{{ $request['id'] }}">
                                            <div class="note-display">
                                                <span class="note-text">{{ !empty($request['note']) ? $request['note'] : '-' }}</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 edit-note-btn" title="Edit Note">
                                                    <i data-feather="edit-2" class="icon-sm"></i>
                                                </button>
                                            </div>
                                            <div class="note-edit d-none">
                                                <textarea class="form-control form-control-sm note-input" rows="2" maxlength="500" placeholder="Tulis note...">{{ $request['note'] ?? '' }}</textarea>
                                                <div class="d-flex gap-1 mt-1">
                                                    <button type="button" class="btn btn-sm btn-primary btn-save-note">Simpan</button>
                                                    <button type="button" class="btn btn-sm btn-secondary btn-cancel-note">Batal</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center text-muted">Belum ada data item outstanding</td>
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
    .note-cell { min-width: 150px; }
    .note-display { display: flex; align-items: center; gap: 5px; }
    .note-text { flex: 1; min-height: 20px; word-wrap: break-word; }
    .note-edit { width: 100%; }
    .note-input { font-size: 0.875rem; }
    .edit-note-btn { opacity: 0.6; transition: opacity 0.2s; }
    .edit-note-btn:hover { opacity: 1; }
    .filter-header { display: flex; flex-direction: column; }
    .filter-select { min-width: 120px; font-size: 0.75rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.table-responsive table');
    const searchDescriptionInput = document.getElementById('searchDescription');
    const filterSelect = document.querySelector('.filter-select[data-column="7"]');

    function populateUserFilter() {
        if (!table || !filterSelect) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const userValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            const userCell = row.cells[7]; // User column index
            if (userCell) {
                const userValue = userCell.textContent.trim();
                if (userValue && userValue !== '-') {
                    userValues.add(userValue);
                }
            }
        });

        // Reset previous dynamic options
        while (filterSelect.options.length > 1) {
            filterSelect.remove(1);
        }

        Array.from(userValues).sort().forEach(user => {
            const option = document.createElement('option');
            option.value = user;
            option.textContent = user;
            filterSelect.appendChild(option);
        });
    }

    function applyFilters() {
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const searchValue = searchDescriptionInput?.value.toLowerCase().trim() || '';
        const filterValue = filterSelect?.value.toLowerCase().trim() || '';

        tbody.querySelectorAll('tr').forEach(row => {
            const descriptionCell = row.cells[1]; // Description column index
            const userCell = row.cells[7]; // User column index

            const descriptionMatch = !searchValue ||
                (descriptionCell && descriptionCell.textContent.toLowerCase().includes(searchValue));
            const userMatch = !filterValue ||
                (userCell && userCell.textContent.trim().toLowerCase() === filterValue);

            row.style.display = (descriptionMatch && userMatch) ? '' : 'none';
        });
    }

    populateUserFilter();
    applyFilters();

    searchDescriptionInput?.addEventListener('input', applyFilters);
    filterSelect?.addEventListener('change', applyFilters);

    // Handle PO dropdown change to update receipt qty
    document.querySelectorAll('.po-select').forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const totalQty = selectedOption.getAttribute('data-total-qty') || '0';
            const row = this.closest('tr');
            const receiptQtyCell = row.querySelector('.receipt-qty-cell');
            
            if (receiptQtyCell) {
                const formattedQty = parseInt(totalQty).toLocaleString('id-ID');
                receiptQtyCell.textContent = formattedQty;
            }
        });
    });

    if (typeof feather !== 'undefined') { 
        feather.replace(); 
        setTimeout(() => feather.replace(), 100);
    }

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
            const requestId = cell.dataset.id;
            const input = cell.querySelector('.note-input');
            const noteText = cell.querySelector('.note-text');
            const display = cell.querySelector('.note-display');
            const edit = cell.querySelector('.note-edit');
            const note = input.value.trim();

            const saveBtn = this; const originalText = saveBtn.textContent; saveBtn.disabled = true; saveBtn.textContent = 'Menyimpan...';

            fetch(`/item_outstanding/note/${requestId}`, {
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

