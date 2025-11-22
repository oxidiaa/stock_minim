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
                                <th style="width: 80px;">Action</th>
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
                                <th>Sched. receipt qty.</th>
                                <th>PO NO.</th>
                                <th>SUPPLIER NAME</th>
                                <th>SUDAH FOLLOW UP?</th>
                                <th>PENGIRIMAN TANGGAL</th>
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
                                    $sudahFollow = $request['sudah_follow'] ?? '';
                                    $pengirimanTanggal = $request['pengiriman_tanggal'] ?? '';
                                    $sudahFollowEditedAt = $request['sudah_follow_edited_at'] ?? null;
                                    $pengirimanTanggalEditedAt = $request['pengiriman_tanggal_edited_at'] ?? null;
                                @endphp
                                <tr class="{{ !empty($request['duplicate_note'] ?? null) ? 'table-warning' : '' }}"
                                    data-po-data="{{ json_encode($poData) }}">
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary open-follow-modal-btn" 
                                                data-item-id="{{ $request['id'] }}"
                                                data-item-code="{{ $request['item_code'] ?? '' }}"
                                                data-item-name="{{ $request['item_name'] ?? '' }}"
                                                data-outstanding="{{ $request['outstanding'] ?? 0 }}"
                                                data-ending-balance="{{ $request['ending_balance'] ?? 0 }}"
                                                data-maximal-stock="{{ $request['maximal_stock'] ?? 0 }}"
                                                data-order-point="{{ $request['order_point'] ?? 0 }}"
                                                data-supplier-name="{{ !empty($poData) ? ($poData[0]['supplier_name'] ?? '-') : '-' }}"
                                                data-po-data="{{ json_encode($poData) }}"
                                                data-has-multiple-po="{{ $hasMultiplePO ? 'true' : 'false' }}"
                                                data-sudah-follow="{{ $sudahFollow }}"
                                                data-pengiriman-tanggal="{{ $pengirimanTanggal ? \Carbon\Carbon::parse($pengirimanTanggal)->format('Y-m-d') : '' }}"
                                                data-qty-akan-dikirim="{{ $request['qty_akan_dikirim'] ?? '' }}"
                                                data-selected-po-no="{{ $request['selected_po_no'] ?? (!empty($poData) ? ($poData[0]['po_no'] ?? '') : '') }}"
                                                title="{{ $sudahFollow ? 'Edit' : 'Tambah' }}">
                                            <i data-feather="{{ $sudahFollow ? 'edit' : 'plus' }}" class="icon-sm"></i>
                                        </button>
                                    </td>
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
                                                                data-supplier-name="{{ $po['supplier_name'] ?? '-' }}"
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
                                    <td class="supplier-name-cell">
                                        @if(!empty($poData))
                                            {{ $poData[0]['supplier_name'] ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($sudahFollow === 'YES')
                                            <span class="badge bg-success">YES</span>
                                        @elseif($sudahFollow === 'NO')
                                            <span class="badge bg-danger">NO</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        @if($sudahFollowEditedAt)
                                            <div style="font-size: 0.75rem; color: #6c757d; margin-top: 4px;">
                                                last edited {{ strtolower(\Carbon\Carbon::parse($sudahFollowEditedAt)->setTimezone('Asia/Jakarta')->format('M d, H:i')) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pengirimanTanggal)
                                            {{ \Carbon\Carbon::parse($pengirimanTanggal)->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        @if($pengirimanTanggalEditedAt)
                                            <div style="font-size: 0.75rem; color: #6c757d; margin-top: 4px;">
                                                last edited {{ strtolower(\Carbon\Carbon::parse($pengirimanTanggalEditedAt)->setTimezone('Asia/Jakarta')->format('M d, H:i')) }}
                                            </div>
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
                                    <td colspan="18" class="text-center text-muted">Belum ada data item outstanding</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Follow Up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followUpModalLabel">Follow Up & Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followUpForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Item Code</label>
                            <input type="text" class="form-control" id="modal_item_code" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" class="form-control" id="modal_item_name" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">OUTSTANDING</label>
                            <input type="text" class="form-control text-end" id="modal_outstanding" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ENDING BALANCE</label>
                            <input type="text" class="form-control text-end" id="modal_ending_balance" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">MAX</label>
                            <input type="text" class="form-control text-end" id="modal_maximal_stock" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ORDER POINT</label>
                            <input type="text" class="form-control text-end" id="modal_order_point" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Supplier Name</label>
                            <input type="text" class="form-control" id="modal_supplier_name" readonly>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3" id="modal_po_no_row" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">NO PO <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_po_no_select" name="po_no" required>
                                <option value="">Pilih NO PO</option>
                            </select>
                            <small class="text-muted">Pilih NO PO untuk menentukan QTY maksimal yang dapat dikirim</small>
                        </div>
                    </div>
                    <div class="row mb-3" id="modal_po_no_single_row" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">NO PO</label>
                            <input type="text" class="form-control" id="modal_po_no_single" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">QTY akan dikirim <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_qty_akan_dikirim" name="qty_akan_dikirim" min="0" max="0" required>
                            <small class="text-muted" id="modal_qty_max_info">Maksimal: <span id="modal_qty_max_value">0</span></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Pengiriman <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_pengiriman_tanggal" name="pengiriman_tanggal" placeholder="dd/mm/yyyy" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">SUDAH FOLLOW UP?</label>
                            <input type="text" class="form-control" id="modal_sudah_follow" readonly style="background-color: #e9ecef;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveFollowUpBtn">Simpan</button>
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
            const userCell = row.cells[8]; // User column index (changed from 7 to 8 due to Action column)
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
            const userCell = row.cells[8]; // User column index (changed from 7 to 8 due to Action column)

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

    // Handle PO dropdown change to update receipt qty and supplier name
    document.querySelectorAll('.po-select').forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const totalQty = selectedOption.getAttribute('data-total-qty') || '0';
            const supplierName = selectedOption.getAttribute('data-supplier-name') || '-';
            const row = this.closest('tr');
            const receiptQtyCell = row.querySelector('.receipt-qty-cell');
            const supplierNameCell = row.querySelector('.supplier-name-cell');
            const modalBtn = row.querySelector('.open-follow-modal-btn');
            
            if (receiptQtyCell) {
                const formattedQty = parseInt(totalQty).toLocaleString('id-ID');
                receiptQtyCell.textContent = formattedQty;
            }
            
            if (supplierNameCell) {
                supplierNameCell.textContent = supplierName;
            }
            
            // Update supplier name in button data attribute
            if (modalBtn) {
                modalBtn.setAttribute('data-supplier-name', supplierName);
            }
        });
    });

    // Handle open follow up modal
    document.querySelectorAll('.open-follow-modal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const modal = new bootstrap.Modal(document.getElementById('followUpModal'));
            
            // Get PO data
            const poDataStr = this.getAttribute('data-po-data') || '[]';
            const poData = JSON.parse(poDataStr);
            const hasMultiplePO = this.getAttribute('data-has-multiple-po') === 'true';
            const selectedPoNo = this.getAttribute('data-selected-po-no') || '';
            
            // Populate modal with data
            document.getElementById('modal_item_code').value = this.getAttribute('data-item-code') || '';
            document.getElementById('modal_item_name').value = this.getAttribute('data-item-name') || '';
            document.getElementById('modal_outstanding').value = parseInt(this.getAttribute('data-outstanding') || 0).toLocaleString('id-ID');
            document.getElementById('modal_ending_balance').value = parseInt(this.getAttribute('data-ending-balance') || 0).toLocaleString('id-ID');
            document.getElementById('modal_maximal_stock').value = parseInt(this.getAttribute('data-maximal-stock') || 0).toLocaleString('id-ID');
            document.getElementById('modal_order_point').value = parseInt(this.getAttribute('data-order-point') || 0).toLocaleString('id-ID');
            
            // Handle NO PO
            const poNoSelect = document.getElementById('modal_po_no_select');
            const poNoSingle = document.getElementById('modal_po_no_single');
            const poNoRow = document.getElementById('modal_po_no_row');
            const poNoSingleRow = document.getElementById('modal_po_no_single_row');
            
            if (hasMultiplePO && poData.length > 1) {
                // Show dropdown for multiple PO
                poNoRow.style.display = 'block';
                poNoSingleRow.style.display = 'none';
                poNoSelect.innerHTML = '<option value="">Pilih NO PO</option>';
                
                poData.forEach((po, index) => {
                    const option = document.createElement('option');
                    option.value = po.po_no || '-';
                    option.textContent = `${po.po_no || '-'} (Qty: ${parseInt(po.total_qty || 0).toLocaleString('id-ID')})`;
                    option.setAttribute('data-total-qty', po.total_qty || 0);
                    option.setAttribute('data-supplier-name', po.supplier_name || '-');
                    if (selectedPoNo && po.po_no === selectedPoNo) {
                        option.selected = true;
                    } else if (!selectedPoNo && index === 0) {
                        option.selected = true;
                    }
                    poNoSelect.appendChild(option);
                });
                
                // Set initial max qty based on selected PO
                const initialSelectedOption = poNoSelect.options[poNoSelect.selectedIndex];
                if (initialSelectedOption && initialSelectedOption.value) {
                    const maxQty = parseInt(initialSelectedOption.getAttribute('data-total-qty') || 0);
                    document.getElementById('modal_qty_akan_dikirim').max = maxQty;
                    document.getElementById('modal_qty_max_value').textContent = maxQty.toLocaleString('id-ID');
                    document.getElementById('modal_supplier_name').value = initialSelectedOption.getAttribute('data-supplier-name') || '-';
                }
            } else if (poData.length > 0) {
                // Show single PO (readonly)
                poNoRow.style.display = 'none';
                poNoSingleRow.style.display = 'block';
                poNoSingle.value = poData[0].po_no || '-';
                
                const maxQty = parseInt(poData[0].total_qty || 0);
                document.getElementById('modal_qty_akan_dikirim').max = maxQty;
                document.getElementById('modal_qty_max_value').textContent = maxQty.toLocaleString('id-ID');
                document.getElementById('modal_supplier_name').value = poData[0].supplier_name || '-';
            } else {
                // No PO data
                poNoRow.style.display = 'none';
                poNoSingleRow.style.display = 'none';
                document.getElementById('modal_qty_akan_dikirim').max = 0;
                document.getElementById('modal_qty_max_value').textContent = '0';
                document.getElementById('modal_supplier_name').value = '-';
            }
            
            // Populate form fields if already filled
            const sudahFollow = this.getAttribute('data-sudah-follow') || '';
            const pengirimanTanggal = this.getAttribute('data-pengiriman-tanggal') || '';
            const qtyAkanDikirim = this.getAttribute('data-qty-akan-dikirim') || '';
            
            document.getElementById('modal_qty_akan_dikirim').value = qtyAkanDikirim;
            
            if (pengirimanTanggal) {
                const dateObj = new Date(pengirimanTanggal);
                if (!isNaN(dateObj.getTime())) {
                    const day = String(dateObj.getDate()).padStart(2, '0');
                    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                    const year = dateObj.getFullYear();
                    document.getElementById('modal_pengiriman_tanggal').value = `${day}/${month}/${year}`;
                }
            } else {
                document.getElementById('modal_pengiriman_tanggal').value = '';
            }
            
            document.getElementById('modal_sudah_follow').value = sudahFollow || '-';
            
            // Set form action
            document.getElementById('followUpForm').setAttribute('data-item-id', itemId);
            
            // Initialize flatpickr for date picker in modal
            const dateInput = document.getElementById('modal_pengiriman_tanggal');
            if (typeof flatpickr !== 'undefined') {
                // Destroy existing flatpickr instance if exists
                if (dateInput._flatpickr) {
                    dateInput._flatpickr.destroy();
                }
                
                flatpickr(dateInput, {
                    dateFormat: "d/m/Y",
                    allowInput: true
                });
            }
            
            // Update modal title
            document.getElementById('followUpModalLabel').textContent = sudahFollow ? 'Edit Follow Up & Pengiriman' : 'Follow Up & Pengiriman';
            
            modal.show();
            
            // Reinitialize feather icons after modal is shown
            if (typeof feather !== 'undefined') {
                setTimeout(() => feather.replace(), 100);
            }
        });
    });
    
    // Handle PO NO dropdown change in modal
    document.getElementById('modal_po_no_select')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const maxQty = parseInt(selectedOption.getAttribute('data-total-qty') || 0);
            const supplierName = selectedOption.getAttribute('data-supplier-name') || '-';
            
            // Update max QTY
            const qtyInput = document.getElementById('modal_qty_akan_dikirim');
            qtyInput.max = maxQty;
            document.getElementById('modal_qty_max_value').textContent = maxQty.toLocaleString('id-ID');
            
            // Update supplier name
            document.getElementById('modal_supplier_name').value = supplierName;
            
            // Validate current QTY value
            const currentQty = parseInt(qtyInput.value || 0);
            if (currentQty > maxQty) {
                qtyInput.value = maxQty;
                alert(`QTY akan dikirim disesuaikan menjadi ${maxQty.toLocaleString('id-ID')} (maksimal yang tersedia)`);
            }
        }
    });
    
    // Handle QTY input change to validate against max
    document.getElementById('modal_qty_akan_dikirim')?.addEventListener('input', function() {
        const currentQty = parseInt(this.value || 0);
        const maxQty = parseInt(this.max || 0);
        
        if (currentQty > maxQty) {
            this.value = maxQty;
            alert(`QTY akan dikirim tidak boleh melebihi ${maxQty.toLocaleString('id-ID')}`);
        }
    });

    // Handle form submission
    document.getElementById('followUpForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const itemId = this.getAttribute('data-item-id');
        const qtyAkanDikirim = parseInt(document.getElementById('modal_qty_akan_dikirim').value || 0);
        const pengirimanTanggal = document.getElementById('modal_pengiriman_tanggal').value;
        const maxQty = parseInt(document.getElementById('modal_qty_akan_dikirim').max || 0);
        
        // Validate QTY tidak melebihi max
        if (qtyAkanDikirim > maxQty) {
            alert(`QTY akan dikirim tidak boleh melebihi ${maxQty.toLocaleString('id-ID')}`);
            return;
        }
        
        // Get selected PO NO
        let selectedPoNo = '';
        const poNoSelect = document.getElementById('modal_po_no_select');
        const poNoSingle = document.getElementById('modal_po_no_single');
        
        if (poNoSelect && poNoSelect.style.display !== 'none') {
            selectedPoNo = poNoSelect.value;
            if (!selectedPoNo) {
                alert('Silakan pilih NO PO terlebih dahulu');
                return;
            }
        } else if (poNoSingle && poNoSingle.value) {
            selectedPoNo = poNoSingle.value;
        }
        
        // Convert date from d/m/Y to Y-m-d
        let formattedDate = '';
        if (pengirimanTanggal) {
            const dateParts = pengirimanTanggal.split('/');
            if (dateParts.length === 3) {
                formattedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
            }
        }
        
        const saveBtn = document.getElementById('saveFollowUpBtn');
        const originalText = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';
        
        fetch(`/item_outstanding/update-follow-up/${itemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                qty_akan_dikirim: qtyAkanDikirim,
                pengiriman_tanggal: formattedDate,
                selected_po_no: selectedPoNo,
                sudah_follow: 'YES' // Auto set to YES when form is submitted
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('followUpModal'));
                modal.hide();
                
                // Reload page to show updated data
                window.location.reload();
            } else {
                alert('Gagal menyimpan data. Silakan coba lagi.');
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi error saat menyimpan data.');
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
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

