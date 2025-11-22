@extends('layouts.app')

@section('title', 'Kedatangan Barang - Import Kedatangan Barang')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">Kedatangan Barang</h4>
                    <div class="d-flex gap-2">
                        <form action="{{ route('kedatangan_barang.importExcel') }}" method="POST" enctype="multipart/form-data" class="d-inline" id="importExcelForm">
                            @csrf
                            <input type="hidden" name="arrival_date" id="arrival_date_hidden">
                            <input type="file" name="excel_file" accept=".xlsx,.xls" id="kedatangan_excel_file" style="display: none;" required>
                            <button type="button" class="btn btn-success" onclick="document.getElementById('kedatangan_excel_file').click()">
                                <i data-feather="upload"></i> Import Excel Kedatangan Barang
                            </button>
                        </form>
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

                <div class="alert alert-info">
                    <strong>Info:</strong> Import Excel akan mengurangi outstanding dari Item Outstanding dan memindahkan item yang datang ke History.
                </div>

                @if(!empty($importSummary) && !empty($importSummary['items']))
                    <div class="card border border-success mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title text-success mb-0">Ringkasan Import Terakhir</h5>
                                <span class="badge bg-success">
                                    {{ $importSummary['item_count'] ?? 0 }} Item
                                </span>
                            </div>
                            <p class="text-muted mb-3">
                                Tanggal kedatangan: <strong>{{ isset($importSummary['arrival_date']) ? date('d/m/Y', strtotime($importSummary['arrival_date'])) : '-' }}</strong>
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th class="text-end">Jumlah Datang</th>
                                            <th class="text-center" style="min-width: 150px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(($importSummary['items'] ?? []) as $index => $summary)
                                            @php
                                                $modalId = 'editKedatanganSummaryModal' . $index;
                                            @endphp
                                            <tr>
                                                <td>{{ $summary['item_code'] ?? '-' }}</td>
                                                <td>{{ $summary['item_name'] ?? '-' }}</td>
                                                <td class="text-end">{{ number_format($summary['arrived_qty'] ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning me-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#{{ $modalId }}">
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('history.destroy', $summary['history_id'] ?? '') }}"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Yakin ingin menghapus item ini dari history?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="redirect_to" value="kedatangan_barang">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="{{ $modalId }}Label">Edit Item Kedatangan</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('history.update', $summary['history_id'] ?? '') }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="redirect_to" value="kedatangan_barang">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Item Code</label>
                                                                    <input type="text" class="form-control" name="item_code" value="{{ $summary['item_code'] ?? '' }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Item Name</label>
                                                                    <input type="text" class="form-control" name="item_name" value="{{ $summary['item_name'] ?? '' }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Jumlah Item yang Datang</label>
                                                                    <input type="number" class="form-control" name="jumlah_item_datang" value="{{ $summary['arrived_qty'] ?? 0 }}" min="0" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tanggal Kedatangan</label>
                                                                    <input type="date" class="form-control" name="arrival_date" value="{{ isset($summary['arrival_date']) ? date('Y-m-d', strtotime($summary['arrival_date'])) : date('Y-m-d') }}" required>
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
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tanggal Kedatangan Import -->
<div class="modal fade" id="importDateModal" tabindex="-1" aria-labelledby="importDateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importDateModalLabel">Tanggal Kedatangan Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tanggal Kedatangan</label>
                    <input type="date" class="form-control" id="import_arrival_date" value="{{ date('Y-m-d') }}" required>
                    <small class="text-muted">Tanggal ini akan digunakan untuk semua item yang datang melalui import Excel.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmImportBtn">Import</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') { feather.replace(); }

    // Import Excel handling
    const importForm = document.getElementById('importExcelForm');
    const excelInput = document.getElementById('kedatangan_excel_file');
    const arrivalHiddenInput = document.getElementById('arrival_date_hidden');
    const importDateModalEl = document.getElementById('importDateModal');
    const importDateModal = importDateModalEl ? new bootstrap.Modal(importDateModalEl) : null;
    const confirmImportBtn = document.getElementById('confirmImportBtn');
    const importArrivalDateInput = document.getElementById('import_arrival_date');

    excelInput?.addEventListener('change', function() {
        if (excelInput.files.length > 0 && importDateModal) {
            importArrivalDateInput.value = new Date().toISOString().split('T')[0];
            importDateModal.show();
        }
    });

    confirmImportBtn?.addEventListener('click', function() {
        if (!excelInput?.files.length) {
            alert('Pilih file Excel terlebih dahulu.');
            return;
        }
        if (!importArrivalDateInput?.value) {
            alert('Pilih tanggal kedatangan.');
            return;
        }
        arrivalHiddenInput.value = importArrivalDateInput.value;
        importDateModal.hide();
        importForm.submit();
    });
});
</script>
@endsection


