@extends('layouts.app')

@section('title', 'Item Minim - Daftar Item Minim')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">Item Minim</h4>
                    <div class="d-flex gap-2">
                        <span class="badge bg-warning text-dark align-self-center">
                            Ending Balance < Min & Outstanding > 0
                        </span>
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
                                <th style="width: 80px;">Action</th>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th>OUTSTANDING</th>
                                <th>Request WHC</th>
                                <th>ENDING BALANCE</th>
                                <th>MAX</th>
                                <th>ORDER POINT</th>
                                <th>MIN</th>

                                @if(auth()->check() && in_array(auth()->user()->username, ['master', 'purchasing']))
                                <th>
                                    <div class="filter-header">
                                        <span>User</span>
                                        <select class="form-select form-select-sm filter-select" data-column="9" style="margin-top: 5px;">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </th>
                                @endif

                                <th>Outstanding PP</th>
                                <th>Sched. receipt qty.</th>
                                <th>PO NO.</th>
                                <th>
                                    <div class="filter-header">
                                        <span>SUPPLIER NAME</span>
                                        <select class="form-select form-select-sm filter-select" data-column="13" style="margin-top: 5px;">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </th>
                                <th>QTY akan dikirim</th>
                                <th>
                                    <div class="filter-header">
                                        <span>SUDAH FOLLOW UP?</span>
                                        <select class="form-select form-select-sm filter-select" data-column="15" style="margin-top: 5px;">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </th>
                                <th>
                                    <div class="filter-header">
                                        <span>PENGIRIMAN TANGGAL</span>
                                        <select class="form-select form-select-sm filter-select" data-column="16" style="margin-top: 5px;">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </th>
                                <th>Import</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($minimItems as $index => $item)
                                @php
                                    $poData = $item['po_data'] ?? [];
                                    $hasMultiplePO = $item['has_multiple_po'] ?? false;
                                    $sudahFollow = $item['sudah_follow'] ?? '';
                                    $pengirimanTanggal = $item['pengiriman_tanggal'] ?? '';
                                    $qtyAkanDikirim = $item['qty_akan_dikirim'] ?? null;
                                    $selectedPoNo = $item['selected_po_no'] ?? '';
                                    $requestWhc = $item['request_whc'] ?? null;
                                    $requestWhcEditedAt = $item['request_whc_edited_at'] ?? null;
                                    $sudahFollowEditedAt = $item['sudah_follow_edited_at'] ?? null;
                                    $pengirimanTanggalEditedAt = $item['pengiriman_tanggal_edited_at'] ?? null;
                                @endphp
                                <tr class="{{ !empty($item['duplicate_note'] ?? null) ? 'table-warning' : '' }}"
                                    data-po-data="{{ json_encode($poData) }}">
                                    <td>
                                        @if(auth()->check() && (auth()->user()->username === 'purchasing' || auth()->user()->username === 'master'))
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary open-follow-modal-btn" 
                                                    data-item-id="{{ $item['id'] }}"
                                                    data-item-code="{{ $item['item_code'] ?? '' }}"
                                                    data-item-name="{{ $item['item_name'] ?? '' }}"
                                                    data-outstanding="{{ $item['outstanding'] ?? 0 }}"
                                                    data-request-whc="{{ $requestWhc !== null ? $requestWhc : '' }}"
                                                    data-ending-balance="{{ $item['ending_balance'] ?? 0 }}"
                                                    data-maximal-stock="{{ $item['maximal_stock'] ?? 0 }}"
                                                    data-order-point="{{ $item['order_point'] ?? 0 }}"
                                                    data-supplier-name="{{ !empty($poData) ? ($poData[0]['supplier_name'] ?? '-') : '-' }}"
                                                    data-po-data="{{ json_encode($poData) }}"
                                                    data-has-multiple-po="{{ $hasMultiplePO ? 'true' : 'false' }}"
                                                    data-sudah-follow="{{ $sudahFollow }}"
                                                    data-pengiriman-tanggal="{{ $pengirimanTanggal ? \Carbon\Carbon::parse($pengirimanTanggal)->format('Y-m-d') : '' }}"
                                                    data-qty-akan-dikirim="{{ $qtyAkanDikirim ?? '' }}"
                                                    data-selected-po-no="{{ $selectedPoNo ?? (!empty($poData) ? ($poData[0]['po_no'] ?? '') : '') }}"
                                                    title="{{ $sudahFollow ? 'Edit Follow Up' : 'Tambah Follow Up' }}">
                                                <i data-feather="{{ $sudahFollow ? 'edit' : 'plus' }}" class="icon-sm"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $item['item_code'] ?? '-' }}</td>
                                    <td>{{ $item['item_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item['outstanding'] ?? 0, 0, ',', '.') }}</td>
                                    
                                    <td>
                                        <div class="request-whc-cell" data-item-id="{{ $item['id'] }}" data-outstanding="{{ $item['outstanding'] ?? 0 }}">
                                            @if(auth()->check() && in_array(auth()->user()->username, ['whc', 'master']))
                                                <input type="number" 
                                                       class="form-control form-control-sm request-whc-input" 
                                                       value="{{ $requestWhc !== null ? $requestWhc : '' }}" 
                                                       min="0" 
                                                       max="{{ $item['outstanding'] ?? 0 }}"
                                                       placeholder="0"
                                                       data-outstanding="{{ $item['outstanding'] ?? 0 }}"
                                                       style="width: 100px; display: inline-block;">
                                            @else
                                                <div class="form-control-plaintext text-end fw-semibold">
                                                    {{ $requestWhc !== null ? number_format($requestWhc, 0, ',', '.') : '-' }}
                                                </div>
                                            @endif
                                            @if($requestWhcEditedAt)
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-top: 4px;">
                                                    last edited {{ strtolower(\Carbon\Carbon::parse($requestWhcEditedAt)->setTimezone('Asia/Jakarta')->format('M d, H:i')) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end text-danger"><strong>{{ number_format($item['ending_balance'] ?? 0, 0, ',', '.') }}</strong></td>
                                    <td class="text-end">{{ number_format($item['maximal_stock'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item['order_point'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item['minimal_stock'] ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $item['user'] ?? '-' }}</td>
                                    <td>{{ $item['outstanding_pp'] ?? '-' }}</td>
                                    <td class="text-end receipt-qty-cell" 
                                        data-total-qty="{{ $item['total_receipt_qty'] ?? 0 }}">
                                        @if(!empty($poData))
                                            @if($hasMultiplePO && count($poData) > 1)
                                                {{ number_format($poData[0]['total_qty'] ?? 0, 0, ',', '.') }}
                                            @else
                                                {{ number_format($item['total_receipt_qty'] ?? 0, 0, ',', '.') }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($poData))
                                            @if($hasMultiplePO && count($poData) > 1)
                                                <select class="form-select form-select-sm po-select" 
                                                        data-item-id="{{ $item['id'] }}"
                                                        style="min-width: 150px;">
                                                    @foreach($poData as $poIndex => $po)
                                                        @php
                                                            $isCurrentItem = isset($po['item_code']) && strtolower(trim($po['item_code'])) === strtolower(trim($item['item_code']));
                                                            $displayText = $po['po_no'];
                                                            if (isset($po['item_code']) && isset($po['item_name'])) {
                                                                $displayText .= ' - ' . $po['item_code'] . ' (' . number_format($po['total_qty'], 0, ',', '.') . ')';
                                                            } else {
                                                                $displayText .= ' (Qty: ' . number_format($po['total_qty'], 0, ',', '.') . ', ' . count($po['items']) . ' item)';
                                                            }
                                                        @endphp
                                                        <option value="{{ $po['po_no'] }}" 
                                                                data-total-qty="{{ $po['total_qty'] }}"
                                                                data-supplier-name="{{ $po['supplier_name'] ?? '-' }}"
                                                                data-item-count="{{ count($po['items']) }}"
                                                                data-item-code="{{ $po['item_code'] ?? '' }}"
                                                                {{ ($selectedPoNo && $selectedPoNo === $po['po_no'] && $isCurrentItem) || (!$selectedPoNo && $isCurrentItem) || (!$selectedPoNo && $poIndex === 0) ? 'selected' : '' }}>
                                                            {{ $displayText }}
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
                                    <td class="text-end">
                                        @if($qtyAkanDikirim !== null && $qtyAkanDikirim !== '')
                                            {{ number_format($qtyAkanDikirim, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sudahFollow === 'YES')
                                            <span class="badge bg-success">YES</span>
                                        @else
                                            <span class="badge bg-danger">NO</span>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="18" class="text-center text-muted">Belum ada item minim (semua item memiliki ending balance >= min atau outstanding = 0)</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" name="user" id="edit_user">
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

<!-- Follow Up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="followUpModalLabel">
                    <i data-feather="truck" class="me-2" style="width: 20px; height: 20px;"></i>
                    Follow Up & Pengiriman
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followUpForm" novalidate>
                @csrf
                <div class="modal-body">
                    <!-- Informasi Item -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i data-feather="package" class="me-2" style="width: 16px; height: 16px;"></i>
                                Informasi Item
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Item Code</label>
                                    <div class="form-control-plaintext fw-semibold" id="modal_item_code" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">-</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Description</label>
                                    <div class="form-control-plaintext fw-semibold" id="modal_item_name" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">-</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small mb-1">OUTSTANDING</label>
                                    <div class="form-control-plaintext text-end fw-semibold text-primary" id="modal_outstanding" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">0</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small mb-1">Request WHC</label>
                                    <div class="form-control-plaintext text-end fw-semibold" id="modal_request_whc" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">-</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small mb-1">ENDING BALANCE</label>
                                    <div class="form-control-plaintext text-end fw-semibold" id="modal_ending_balance" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">0</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small mb-1">MAX</label>
                                    <div class="form-control-plaintext text-end fw-semibold" id="modal_maximal_stock" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">0</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">ORDER POINT</label>
                                    <div class="form-control-plaintext text-end fw-semibold" id="modal_order_point" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">0</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Supplier Name</label>
                                    <div class="form-control-plaintext fw-semibold" id="modal_supplier_name" style="min-height: 38px; padding: 0.375rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi PO & Pengiriman -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold text-success">
                                <i data-feather="file-text" class="me-2" style="width: 16px; height: 16px;"></i>
                                Informasi PO & Pengiriman
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- NO PO Multiple -->
                                <div class="col-12" id="modal_po_no_row" style="display: none;">
                                    <label class="form-label fw-semibold">
                                        NO PO <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-lg" id="modal_po_no_select" name="po_no" required>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        <i data-feather="info" class="me-1" style="width: 14px; height: 14px;"></i>
                                        Pilih NO PO untuk menentukan QTY maksimal yang dapat dikirim
                                    </small>
                                </div>
                                <!-- NO PO Single -->
                                <div class="col-12" id="modal_po_no_single_row" style="display: none;">
                                    <label class="form-label fw-semibold">NO PO</label>
                                    <div class="form-control-plaintext fw-semibold" id="modal_po_no_single" style="min-height: 48px; padding: 0.5rem 0.75rem; background-color: #f8f9fa; border-radius: 0.375rem;">-</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        QTY akan dikirim <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control form-control-lg" 
                                           id="modal_qty_akan_dikirim" 
                                           name="qty_akan_dikirim" 
                                           min="0" 
                                           max="0" 
                                           required
                                           placeholder="Masukkan jumlah QTY">
                                    <div class="mt-2">
                                        <span class="badge bg-info" id="modal_qty_max_info">
                                            <i data-feather="alert-circle" class="me-1" style="width: 14px; height: 14px;"></i>
                                            Maksimal: <span id="modal_qty_max_value" class="fw-bold">0</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Date Pengiriman <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="modal_pengiriman_tanggal" 
                                           name="pengiriman_tanggal" 
                                           placeholder="dd/mm/yyyy">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="modal_tanggal_belum_ditentukan" 
                                               name="tanggal_belum_ditentukan">
                                        <label class="form-check-label" for="modal_tanggal_belum_ditentukan">
                                            Tanggal belum ditentukan
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                                        Pilih tanggal pengiriman atau centang "Tanggal belum ditentukan"
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Follow Up -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold text-warning">
                                <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                                Status Follow Up
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">SUDAH FOLLOW UP?</label>
                                    <div class="mt-2">
                                        <span class="badge bg-secondary fs-6 px-3 py-2" id="modal_sudah_follow">-</span>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Status akan otomatis menjadi "YES" setelah data disimpan
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <div class="d-flex justify-content-end w-100 gap-2">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                            <i data-feather="x" class="me-2" style="width: 18px; height: 18px;"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg" id="saveFollowUpBtn">
                            <i data-feather="check" class="me-2" style="width: 18px; height: 18px;"></i>
                            Simpan
                        </button>
                    </div>
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
    
    /* Modal Styling */
    #followUpModal .modal-content {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    #followUpModal .card {
        transition: box-shadow 0.2s;
    }
    
    #followUpModal .card:hover {
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
    }
    
    #followUpModal .form-control-plaintext {
        border: 1px solid #dee2e6;
    }
    
    #followUpModal .form-control-lg,
    #followUpModal .form-select-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    #followUpModal .card-header {
        border-bottom: 2px solid #dee2e6;
        padding: 0.75rem 1.25rem;
    }
    
    #followUpModal .badge {
        font-weight: 500;
    }
    
    #followUpModal input[readonly],
    #followUpModal .form-control-plaintext {
        cursor: default;
    }
    
    #followUpModal .text-muted {
        font-size: 0.875rem;
    }
    
    #followUpModal .modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
    }
    
    #followUpModal .btn-lg {
        min-width: 120px;
        font-weight: 600;
    }
    
    #followUpModal .modal-dialog-scrollable {
        max-height: calc(100vh - 1rem);
    }
    
    #followUpModal .modal-dialog-scrollable .modal-content {
        max-height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    #followUpModal .modal-dialog-scrollable .modal-body {
        overflow-y: auto;
        overflow-x: hidden;
        flex: 1 1 auto;
        max-height: calc(100vh - 250px);
    }
    
    #followUpModal .modal-body {
        padding: 1.5rem;
    }
    
    /* Custom scrollbar untuk modal */
    #followUpModal .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    #followUpModal .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    #followUpModal .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    #followUpModal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') { feather.replace(); }

    const table = document.querySelector('.table-responsive table');
    const searchDescriptionInput = document.getElementById('searchDescription');
    const filterSelect = document.querySelector('.filter-select[data-column="9"]'); // User filter
    const supplierFilterSelect = document.querySelector('.filter-select[data-column="13"]'); // Supplier Name filter
    const sudahFollowFilterSelect = document.querySelector('.filter-select[data-column="15"]'); // SUDAH FOLLOW UP filter
    const pengirimanTanggalFilterSelect = document.querySelector('.filter-select[data-column="16"]'); // Pengiriman Tanggal filter

    function populateUserFilter() {
        if (!table || !filterSelect) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        if (!filterSelect || !tbody) return;

        // Get all unique user values from table
        const userValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            const userCell = row.cells[9]; // User column index (changed from 8 to 9 due to Request WHC column)
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

    function populateSupplierFilter() {
        if (!table || !supplierFilterSelect) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        // Get all unique supplier values from table
        const supplierValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            const supplierCell = row.cells[13]; // Supplier Name column index
            if (supplierCell) {
                const supplierValue = supplierCell.textContent.trim();
                if (supplierValue && supplierValue !== '-') {
                    supplierValues.add(supplierValue);
                }
            }
        });

        // Reset previous dynamic options
        while (supplierFilterSelect.options.length > 1) {
            supplierFilterSelect.remove(1);
        }

        Array.from(supplierValues).sort().forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier;
            option.textContent = supplier;
            supplierFilterSelect.appendChild(option);
        });
    }

    function populateSudahFollowFilter() {
        if (!table || !sudahFollowFilterSelect) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const sudahFollowValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            const sudahFollowCell = row.cells[15]; // SUDAH FOLLOW UP column index
            if (sudahFollowCell) {
                // Get text content, but exclude the "last edited" part
                const cellText = sudahFollowCell.textContent.trim();
                // Extract status part (before "last edited" if exists)
                const statusPart = cellText.split('last edited')[0].trim();
                // Normalize to YES or NO (default to NO if empty or not YES)
                if (statusPart.toUpperCase() === 'YES' || statusPart.includes('YES')) {
                    sudahFollowValues.add('YES');
                } else {
                    // Everything else (NO, empty, or "-") is treated as NO
                    sudahFollowValues.add('NO');
                }
            }
        });

        // Reset previous dynamic options
        while (sudahFollowFilterSelect.options.length > 1) {
            sudahFollowFilterSelect.remove(1);
        }

        // Always add YES and NO options (since NO is default for empty values)
        if (sudahFollowValues.has('YES')) {
            const option = document.createElement('option');
            option.value = 'YES';
            option.textContent = 'YES';
            sudahFollowFilterSelect.appendChild(option);
        }
        // Always add NO option since it's the default
        const noOption = document.createElement('option');
        noOption.value = 'NO';
        noOption.textContent = 'NO';
        sudahFollowFilterSelect.appendChild(noOption);
    }

    function populatePengirimanTanggalFilter() {
        if (!table || !pengirimanTanggalFilterSelect) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        // Get all unique date values from table
        const tanggalValues = new Set();
        tbody.querySelectorAll('tr').forEach(row => {
            const tanggalCell = row.cells[16]; // Pengiriman Tanggal column index
            if (tanggalCell) {
                // Get text content, but exclude the "last edited" part
                const cellText = tanggalCell.textContent.trim();
                // Extract date part (before "last edited" if exists)
                const datePart = cellText.split('last edited')[0].trim();
                if (datePart && datePart !== '-' && datePart !== '') {
                    tanggalValues.add(datePart);
                }
            }
        });

        // Populate filter dropdown
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
            pengirimanTanggalFilterSelect.appendChild(option);
        });
    }

    function applyFilters() {
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const searchValue = searchDescriptionInput?.value.toLowerCase().trim() || '';
        const userFilterValue = filterSelect?.value.toLowerCase().trim() || '';
        const supplierFilterValue = supplierFilterSelect?.value.toLowerCase().trim() || '';
        const sudahFollowFilterValue = sudahFollowFilterSelect?.value.trim() || '';
        const pengirimanTanggalFilterValue = pengirimanTanggalFilterSelect?.value.trim() || '';

        tbody.querySelectorAll('tr').forEach(row => {
            const descriptionCell = row.cells[2]; // Description column index (0=Action, 1=Item Code, 2=Description)
            const userCell = row.cells[9]; // User column index
            const supplierCell = row.cells[13]; // Supplier Name column index
            const sudahFollowCell = row.cells[15]; // SUDAH FOLLOW UP column index
            const tanggalCell = row.cells[16]; // Pengiriman Tanggal column index

            const descriptionMatch = !searchValue ||
                (descriptionCell && descriptionCell.textContent.toLowerCase().includes(searchValue));
            const userMatch = !userFilterValue ||
                (userCell && userCell.textContent.trim().toLowerCase() === userFilterValue);
            const supplierMatch = !supplierFilterValue ||
                (supplierCell && supplierCell.textContent.trim().toLowerCase() === supplierFilterValue);
            
            // Match sudah follow up
            let sudahFollowMatch = true;
            if (sudahFollowFilterValue) {
                if (sudahFollowCell) {
                    const cellText = sudahFollowCell.textContent.trim();
                    const statusPart = cellText.split('last edited')[0].trim();
                    if (sudahFollowFilterValue === 'YES') {
                        sudahFollowMatch = statusPart.includes('YES') || statusPart.toUpperCase() === 'YES';
                    } else if (sudahFollowFilterValue === 'NO') {
                        // NO matches if it contains NO or is empty/not YES
                        sudahFollowMatch = !statusPart.includes('YES') || statusPart.includes('NO') || statusPart.toUpperCase() === 'NO';
                    }
                } else {
                    // If cell doesn't exist, treat as NO
                    sudahFollowMatch = sudahFollowFilterValue === 'NO';
                }
            }
            
            // Match pengiriman tanggal (extract date part before "last edited")
            let tanggalMatch = true;
            if (pengirimanTanggalFilterValue) {
                if (tanggalCell) {
                    const cellText = tanggalCell.textContent.trim();
                    const datePart = cellText.split('last edited')[0].trim();
                    tanggalMatch = datePart === pengirimanTanggalFilterValue;
                } else {
                    tanggalMatch = false;
                }
            }

            row.style.display = (descriptionMatch && userMatch && supplierMatch && sudahFollowMatch && tanggalMatch) ? '' : 'none';
        });
    }

    populateUserFilter();
    populateSupplierFilter();
    populateSudahFollowFilter();
    populatePengirimanTanggalFilter();
    applyFilters();

    searchDescriptionInput?.addEventListener('input', applyFilters);
    filterSelect?.addEventListener('change', applyFilters);
    supplierFilterSelect?.addEventListener('change', applyFilters);
    sudahFollowFilterSelect?.addEventListener('change', applyFilters);
    pengirimanTanggalFilterSelect?.addEventListener('change', applyFilters);

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
                modalBtn.setAttribute('data-selected-po-no', this.value);
            }
            
            // Re-populate supplier filter after supplier name changes
            if (typeof populateSupplierFilter === 'function') {
                populateSupplierFilter();
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
            document.getElementById('modal_item_code').textContent = this.getAttribute('data-item-code') || '-';
            document.getElementById('modal_item_name').textContent = this.getAttribute('data-item-name') || '-';
            document.getElementById('modal_outstanding').textContent = parseInt(this.getAttribute('data-outstanding') || 0).toLocaleString('id-ID');
            const requestWhc = this.getAttribute('data-request-whc') || '';
            document.getElementById('modal_request_whc').textContent = requestWhc !== '' ? parseInt(requestWhc).toLocaleString('id-ID') : '-';
            document.getElementById('modal_ending_balance').textContent = parseInt(this.getAttribute('data-ending-balance') || 0).toLocaleString('id-ID');
            document.getElementById('modal_maximal_stock').textContent = parseInt(this.getAttribute('data-maximal-stock') || 0).toLocaleString('id-ID');
            document.getElementById('modal_order_point').textContent = parseInt(this.getAttribute('data-order-point') || 0).toLocaleString('id-ID');
            
            // Handle NO PO
            const poNoSelect = document.getElementById('modal_po_no_select');
            const poNoSingle = document.getElementById('modal_po_no_single');
            const poNoRow = document.getElementById('modal_po_no_row');
            const poNoSingleRow = document.getElementById('modal_po_no_single_row');
            
            if (hasMultiplePO && poData.length > 1) {
                // Show dropdown for multiple PO
                poNoRow.style.display = 'block';
                poNoSingleRow.style.display = 'none';
                poNoSelect.innerHTML = '';
                
                const currentItemCode = this.getAttribute('data-item-code') || '';
                
                poData.forEach((po, index) => {
                    const option = document.createElement('option');
                    option.value = po.po_no || '-';
                    
                    // Check if this is a duplicate PO (has item_code field)
                    let displayText = po.po_no || '-';
                    if (po.item_code && po.item_name) {
                        // Duplicate PO - show item code and qty
                        displayText += ` - ${po.item_code} (${parseInt(po.total_qty || 0).toLocaleString('id-ID')})`;
                    } else {
                        // Multiple PO for same item - show qty and item count
                        displayText += ` (Qty: ${parseInt(po.total_qty || 0).toLocaleString('id-ID')}, ${po.items ? po.items.length : 1} item)`;
                    }
                    
                    option.textContent = displayText;
                    option.setAttribute('data-total-qty', po.total_qty || 0);
                    option.setAttribute('data-supplier-name', po.supplier_name || '-');
                    option.setAttribute('data-item-code', po.item_code || '');
                    
                    // Select current item's PO by default
                    const isCurrentItem = po.item_code && po.item_code.toLowerCase().trim() === currentItemCode.toLowerCase().trim();
                    if (selectedPoNo && po.po_no === selectedPoNo && isCurrentItem) {
                        option.selected = true;
                    } else if (!selectedPoNo && isCurrentItem) {
                        option.selected = true;
                    } else if (!selectedPoNo && index === 0 && !po.item_code) {
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
                    document.getElementById('modal_supplier_name').textContent = initialSelectedOption.getAttribute('data-supplier-name') || '-';
                }
            } else if (poData.length > 0) {
                // Show single PO (readonly)
                poNoRow.style.display = 'none';
                poNoSingleRow.style.display = 'block';
                poNoSingle.textContent = poData[0].po_no || '-';
                
                const maxQty = parseInt(poData[0].total_qty || 0);
                document.getElementById('modal_qty_akan_dikirim').max = maxQty;
                document.getElementById('modal_qty_max_value').textContent = maxQty.toLocaleString('id-ID');
                document.getElementById('modal_supplier_name').textContent = poData[0].supplier_name || '-';
            } else {
                // No PO data
                poNoRow.style.display = 'none';
                poNoSingleRow.style.display = 'none';
                document.getElementById('modal_qty_akan_dikirim').max = 0;
                document.getElementById('modal_qty_max_value').textContent = '0';
                document.getElementById('modal_supplier_name').textContent = '-';
            }
            
            // Populate form fields if already filled
            const sudahFollow = this.getAttribute('data-sudah-follow') || '';
            const pengirimanTanggal = this.getAttribute('data-pengiriman-tanggal') || '';
            const qtyAkanDikirim = this.getAttribute('data-qty-akan-dikirim') || '';
            
            document.getElementById('modal_qty_akan_dikirim').value = qtyAkanDikirim;
            
            const tanggalBelumDitentukanCheckbox = document.getElementById('modal_tanggal_belum_ditentukan');
            
            if (pengirimanTanggal) {
                const dateObj = new Date(pengirimanTanggal);
                if (!isNaN(dateObj.getTime())) {
                    const day = String(dateObj.getDate()).padStart(2, '0');
                    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                    const year = dateObj.getFullYear();
                    document.getElementById('modal_pengiriman_tanggal').value = `${day}/${month}/${year}`;
                    if (tanggalBelumDitentukanCheckbox) {
                        tanggalBelumDitentukanCheckbox.checked = false;
                    }
                }
            } else {
                document.getElementById('modal_pengiriman_tanggal').value = '';
                if (tanggalBelumDitentukanCheckbox) {
                    tanggalBelumDitentukanCheckbox.checked = true;
                }
            }
            
            // Update SUDAH FOLLOW badge
            const sudahFollowBadge = document.getElementById('modal_sudah_follow');
            if (sudahFollow === 'YES') {
                sudahFollowBadge.textContent = 'YES';
                sudahFollowBadge.className = 'badge bg-success fs-6 px-3 py-2';
            } else if (sudahFollow === 'NO') {
                sudahFollowBadge.textContent = 'NO';
                sudahFollowBadge.className = 'badge bg-danger fs-6 px-3 py-2';
            } else {
                sudahFollowBadge.textContent = '-';
                sudahFollowBadge.className = 'badge bg-secondary fs-6 px-3 py-2';
            }
            
            // Set form action
            document.getElementById('followUpForm').setAttribute('data-item-id', itemId);
            
            // Initialize flatpickr for date picker in modal (only if checkbox is not checked)
            const dateInput = document.getElementById('modal_pengiriman_tanggal');
            const checkbox = document.getElementById('modal_tanggal_belum_ditentukan');
            if (typeof flatpickr !== 'undefined' && dateInput) {
                // Destroy existing flatpickr instance if exists
                if (dateInput._flatpickr) {
                    dateInput._flatpickr.destroy();
                }
                
                // Only initialize flatpickr if checkbox is not checked
                if (!checkbox || !checkbox.checked) {
                    flatpickr(dateInput, {
                        dateFormat: "d/m/Y",
                        allowInput: true
                    });
                }
            }
            
            // Update modal title
            document.getElementById('followUpModalLabel').textContent = sudahFollow ? 'Edit Follow Up & Pengiriman' : 'Follow Up & Pengiriman';
            
            modal.show();
            
            // Reinitialize feather icons after modal is shown
            if (typeof feather !== 'undefined') {
                setTimeout(() => {
                    feather.replace();
                    setTimeout(() => feather.replace(), 200);
                }, 100);
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
            document.getElementById('modal_supplier_name').textContent = supplierName;
            
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

    // Handle checkbox and date input - make them mutually exclusive
    const tanggalBelumDitentukanCheckbox = document.getElementById('modal_tanggal_belum_ditentukan');
    const pengirimanTanggalInput = document.getElementById('modal_pengiriman_tanggal');
    
    if (tanggalBelumDitentukanCheckbox && pengirimanTanggalInput) {
        // When checkbox is checked, clear date input
        tanggalBelumDitentukanCheckbox.addEventListener('change', function() {
            if (this.checked) {
                pengirimanTanggalInput.value = '';
                // Destroy flatpickr instance if exists
                if (pengirimanTanggalInput._flatpickr) {
                    pengirimanTanggalInput._flatpickr.destroy();
                }
            } else {
                // When checkbox is unchecked, initialize flatpickr if not already initialized
                if (typeof flatpickr !== 'undefined' && !pengirimanTanggalInput._flatpickr) {
                    flatpickr(pengirimanTanggalInput, {
                        dateFormat: "d/m/Y",
                        allowInput: true
                    });
                }
            }
        });
        
        // When date input has value, uncheck checkbox
        pengirimanTanggalInput.addEventListener('change', function() {
            if (this.value && this.value.trim() !== '') {
                tanggalBelumDitentukanCheckbox.checked = false;
            }
        });
        
        // Also handle input event for real-time updates
        pengirimanTanggalInput.addEventListener('input', function() {
            if (this.value && this.value.trim() !== '') {
                tanggalBelumDitentukanCheckbox.checked = false;
            }
        });
    }

    // Handle form submission
    function handleFollowUpFormSubmit(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const form = e.target;
        const itemId = form.getAttribute('data-item-id');
        
        if (!itemId) {
            alert('Item ID tidak ditemukan. Silakan tutup modal dan coba lagi.');
            return;
        }
        
        const qtyAkanDikirim = parseInt(document.getElementById('modal_qty_akan_dikirim').value || 0);
        const pengirimanTanggal = document.getElementById('modal_pengiriman_tanggal').value;
        const maxQty = parseInt(document.getElementById('modal_qty_akan_dikirim').max || 0);
        
        // Validate required fields - must have either date or checkbox checked
        const tanggalBelumDitentukan = document.getElementById('modal_tanggal_belum_ditentukan').checked;
        if (!pengirimanTanggal && !tanggalBelumDitentukan) {
            alert('Silakan isi Date Pengiriman atau centang "Tanggal belum ditentukan".');
            return;
        }
        
        // Validate QTY tidak melebihi max
        if (maxQty > 0 && qtyAkanDikirim > maxQty) {
            alert(`QTY akan dikirim tidak boleh melebihi ${maxQty.toLocaleString('id-ID')}`);
            return;
        }
        
        // Get selected PO NO
        let selectedPoNo = '';
        const poNoSelect = document.getElementById('modal_po_no_select');
        const poNoSingle = document.getElementById('modal_po_no_single');
        const poNoRow = document.getElementById('modal_po_no_row');
        const poNoSingleRow = document.getElementById('modal_po_no_single_row');
        
        // Check if multiple PO dropdown is visible
        if (poNoRow && poNoRow.style.display !== 'none' && poNoSelect) {
            selectedPoNo = poNoSelect.value;
            if (!selectedPoNo) {
                alert('Silakan pilih NO PO terlebih dahulu');
                return;
            }
        } else if (poNoSingleRow && poNoSingleRow.style.display !== 'none' && poNoSingle) {
            // For single PO, get text content (not value)
            selectedPoNo = poNoSingle.textContent.trim();
            if (selectedPoNo === '-' || !selectedPoNo) {
                selectedPoNo = '';
            }
        }
        
        // Convert date from d/m/Y to Y-m-d
        // If checkbox is checked, set empty string for tanggal
        let formattedDate = '';
        if (tanggalBelumDitentukan) {
            formattedDate = ''; // Empty string for "Tanggal belum ditentukan"
        } else if (pengirimanTanggal) {
            const dateParts = pengirimanTanggal.split('/');
            if (dateParts.length === 3) {
                formattedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
            }
        }
        
        const saveBtn = document.getElementById('saveFollowUpBtn');
        if (!saveBtn) {
            alert('Tombol simpan tidak ditemukan.');
            return;
        }
        
        const originalText = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';
        
        fetch(`/item_minim/update-follow-up/${itemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                qty_akan_dikirim: qtyAkanDikirim,
                pengiriman_tanggal: formattedDate,
                selected_po_no: selectedPoNo,
                sudah_follow: 'YES'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('followUpModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Reload page to show updated data
                window.location.reload();
            } else {
                alert('Gagal menyimpan data: ' + (data.message || 'Silakan coba lagi.'));
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi error saat menyimpan data: ' + error.message);
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        });
    }
    
    // Attach event listener to form
    const followUpForm = document.getElementById('followUpForm');
    if (followUpForm) {
        followUpForm.addEventListener('submit', handleFollowUpFormSubmit);
    }
    
    // Also use event delegation as backup
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'followUpForm') {
            handleFollowUpFormSubmit(e);
        }
    });

    // Handle Request WHC input changes
    document.querySelectorAll('.request-whc-input').forEach(input => {
        let timeoutId;
        const cell = input.closest('.request-whc-cell');
        const itemId = cell.dataset.itemId;
        const outstanding = parseInt(cell.dataset.outstanding || input.dataset.outstanding || 0);
        
        // Set max attribute based on outstanding
        input.max = outstanding;
        
        // Handle input change with validation
        input.addEventListener('input', function() {
            const inputValue = parseInt(this.value || 0);
            const maxValue = parseInt(this.max || outstanding);
            
            // Validate in real-time
            if (this.value !== '' && !isNaN(inputValue)) {
                if (inputValue > maxValue) {
                    this.value = maxValue;
                    alert(`Request WHC tidak boleh melebihi jumlah Outstanding (${maxValue.toLocaleString('id-ID')})`);
                    return;
                }
            }
            
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                updateRequestWhc(itemId, this.value, outstanding);
            }, 1000); // Wait 1 second after user stops typing
        });
        
        // Handle Enter key press
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const inputValue = parseInt(this.value || 0);
                const maxValue = parseInt(this.max || outstanding);
                
                if (this.value !== '' && !isNaN(inputValue) && inputValue > maxValue) {
                    this.value = maxValue;
                    alert(`Request WHC tidak boleh melebihi jumlah Outstanding (${maxValue.toLocaleString('id-ID')})`);
                    return;
                }
                
                clearTimeout(timeoutId);
                updateRequestWhc(itemId, this.value, outstanding);
            }
        });
        
        // Handle blur (when user clicks outside)
        input.addEventListener('blur', function() {
            const inputValue = parseInt(this.value || 0);
            const maxValue = parseInt(this.max || outstanding);
            
            if (this.value !== '' && !isNaN(inputValue) && inputValue > maxValue) {
                this.value = maxValue;
                alert(`Request WHC tidak boleh melebihi jumlah Outstanding (${maxValue.toLocaleString('id-ID')})`);
            }
            
            clearTimeout(timeoutId);
            updateRequestWhc(itemId, this.value, outstanding);
        });
    });
    
    function updateRequestWhc(itemId, value, outstanding) {
        const requestWhcValue = value === '' ? null : parseInt(value);
        
        if (requestWhcValue !== null && isNaN(requestWhcValue)) {
            return; // Invalid value, skip update
        }
        
        // Validate against outstanding
        if (requestWhcValue !== null && requestWhcValue > outstanding) {
            alert(`Request WHC tidak boleh melebihi jumlah Outstanding (${outstanding.toLocaleString('id-ID')})`);
            // Reset input to max value
            const input = document.querySelector(`.request-whc-cell[data-item-id="${itemId}"] .request-whc-input`);
            if (input) {
                input.value = outstanding;
            }
            return;
        }
        
        fetch(`/item_outstanding/update-request-whc/${itemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({ request_whc: requestWhcValue })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the last edited timestamp in the UI
                const cell = document.querySelector(`.request-whc-cell[data-item-id="${itemId}"]`);
                if (cell) {
                    let lastEditedDiv = cell.querySelector('.last-edited-whc');
                    if (!lastEditedDiv) {
                        lastEditedDiv = document.createElement('div');
                        lastEditedDiv.className = 'last-edited-whc';
                        lastEditedDiv.style.cssText = 'font-size: 0.75rem; color: #6c757d; margin-top: 4px;';
                        cell.appendChild(lastEditedDiv);
                    }
                    lastEditedDiv.textContent = 'last edited ' + data.last_edited;
                }
                
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            } else {
                alert('Gagal memperbarui Request WHC: ' + (data.message || 'Unknown error'));
                // Reset input if validation failed
                if (data.message && data.message.includes('melebihi')) {
                    const input = document.querySelector(`.request-whc-cell[data-item-id="${itemId}"] .request-whc-input`);
                    if (input) {
                        const outstanding = parseInt(input.dataset.outstanding || 0);
                        input.value = outstanding;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating Request WHC:', error);
            alert('Terjadi error saat memperbarui Request WHC.');
        });
    }

    if (typeof feather !== 'undefined') { 
        feather.replace(); 
        setTimeout(() => feather.replace(), 100);
    }

    // Edit item functionality
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('editItemForm');
            const itemId = this.dataset.id;
            form.action = `/item_minim/${itemId}`;
            
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

            fetch(`/item_minim/note/${itemId}`, {
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
