<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\History;
use App\Models\KedatanganBarang;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\ItemMaster;

class HistoryController extends Controller
{
    /**
     * Display a listing of history items.
     */
    public function index()
    {
        // Ambil data dari tabel kedatangan_barangs, urutkan arrival_date desc
        $historyData = KedatanganBarang::orderBy('arrival_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $historyItems = $historyData->map(function ($item) {
            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'supplier_name' => $item->supplier_name,
                'scheduled_receipt_qty' => $item->scheduled_receipt_qty,
                'request_whc' => $item->request_whc,
                'request_whc_date' => $item->request_whc_date,
                'po_no' => $item->po_no,
                'jumlah_item_datang' => $item->arrived_qty, // mapping
                'arrival_date' => $item->arrival_date,
                'pengiriman_tanggal' => $item->pengiriman_tanggal,
                'edited_at' => $item->updated_at,
            ];
        });

        return view('pages.history', compact('historyItems'));
    }

    /**
     * Update a history item (kedatangan_barang).
     */
    public function update(Request $request, $id)
    {
        $this->abortIfGuest();
        
        $validated = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'scheduled_receipt_qty' => 'nullable|integer|min:0',
            'po_no' => 'nullable|string|max:255',
            'jumlah_item_datang' => 'required|integer|min:0',
            'arrival_date' => 'required|date',
            'pengiriman_tanggal' => 'nullable|date',
        ]);

        try {
            $kedatangan = KedatanganBarang::findOrFail($id);

            $kedatangan->update([
                'item_code' => $validated['item_code'],
                'item_name' => $validated['item_name'],
                'supplier_name' => $validated['supplier_name'] ?? '',
                'scheduled_receipt_qty' => $validated['scheduled_receipt_qty'] ?? 0,
                'po_no' => $validated['po_no'] ?? '',
                'arrived_qty' => $validated['jumlah_item_datang'],
                'arrival_date' => $validated['arrival_date'],
                'pengiriman_tanggal' => $validated['pengiriman_tanggal'] ?? null,
            ]);

            // Sync legacy session summaries (optional)
            $this->syncSessionSummaries($id, [
                'item_code' => $kedatangan->item_code,
                'item_name' => $kedatangan->item_name,
                'supplier_name' => $kedatangan->supplier_name,
                'scheduled_receipt_qty' => $kedatangan->scheduled_receipt_qty,
                'po_no' => $kedatangan->po_no,
                'jumlah_item_datang' => $kedatangan->arrived_qty,
                'arrival_date' => $kedatangan->arrival_date,
            ]);

            $redirectTo = $request->input('redirect_to');
            $message = 'Data history berhasil diperbarui.';

            if ($redirectTo === 'kedatangan_barang') {
                return redirect()->route('kedatangan_barang.index')->with('success', $message);
            }

            return redirect()->route('history.index')->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating history: ' . $e->getMessage());
        }
    }

    /**
     * Delete a history item (kedatangan_barang).
     */
    public function destroy(Request $request, $id)
    {
        $this->abortIfGuest();
        
        try {
            $kedatangan = KedatanganBarang::findOrFail($id);
            $kedatangan->delete();

            // Sync with session summaries (legacy cleanup)
            $this->startSessionRemoval($id);

            $redirectTo = $request->input('redirect_to');
            $message = 'Item history berhasil dihapus.';

            if ($redirectTo === 'kedatangan_barang') {
                return redirect()->route('kedatangan_barang.index')->with('success', $message);
            }

            return redirect()->route('history.index')->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting history: ' . $e->getMessage());
        }
    }

    /**
     * Helper to sync updates to session summaries (Legacy support)
     */
    private function syncSessionSummaries($id, $data)
    {
        // Update processing import summary if exists
        $importSummary = Session::get('processing_import_summary', ['items' => [], 'item_count' => 0]);

        if (!empty($importSummary['items'])) {
            foreach ($importSummary['items'] as &$summaryItem) {
                if (($summaryItem['history_id'] ?? '') == $id) {
                    $summaryItem['item_code'] = $data['item_code'];
                    $summaryItem['item_name'] = $data['item_name'];
                    $summaryItem['arrived_qty'] = $data['jumlah_item_datang'];
                    $summaryItem['arrival_date'] = $data['arrival_date'];
                    break;
                }
            }
            unset($summaryItem);
            Session::put('processing_import_summary', $importSummary);
        }

        // Update kedatangan import summary if exists
        $kedatanganSummary = Session::get('kedatangan_import_summary', ['items' => [], 'item_count' => 0]);

        if (!empty($kedatanganSummary['items'])) {
            foreach ($kedatanganSummary['items'] as &$summaryItem) {
                if (($summaryItem['history_id'] ?? '') == $id) {
                    $summaryItem['item_code'] = $data['item_code'];
                    $summaryItem['item_name'] = $data['item_name'];
                    $summaryItem['supplier_name'] = $data['supplier_name'] ?? '';
                    $summaryItem['scheduled_receipt_qty'] = $data['scheduled_receipt_qty'] ?? 0;
                    $summaryItem['po_no'] = $data['po_no'] ?? '';
                    $summaryItem['arrived_qty'] = $data['jumlah_item_datang'];
                    $summaryItem['arrival_date'] = $data['arrival_date'];
                    break;
                }
            }
            unset($summaryItem);
            Session::put('kedatangan_import_summary', $kedatanganSummary);
        }
    }

    /**
     * Helper to remove items from session summaries
     */
    private function startSessionRemoval($id)
    {
        // Update processing import summary if exists
        $importSummary = Session::get('processing_import_summary');
        if (!empty($importSummary['items'])) {
            $importSummary['items'] = array_values(array_filter($importSummary['items'], function ($summaryItem) use ($id) {
                return ($summaryItem['history_id'] ?? '') != $id;
            }));
            $importSummary['item_count'] = count($importSummary['items']);
            Session::put('processing_import_summary', $importSummary);
        }

        // Update kedatangan import summary if exists
        $kedatanganSummary = Session::get('kedatangan_import_summary');
        if (!empty($kedatanganSummary['items'])) {
            $kedatanganSummary['items'] = array_values(array_filter($kedatanganSummary['items'], function ($summaryItem) use ($id) {
                return ($summaryItem['history_id'] ?? '') != $id;
            }));
            $kedatanganSummary['item_count'] = count($kedatanganSummary['items']);
            Session::put('kedatangan_import_summary', $kedatanganSummary);
        }
    }

    /**
     * Export history data to Excel
     */
    public function export(Request $request)
    {
        // Start query
        $query = KedatanganBarang::query()
            ->orderBy('arrival_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Check for arrival_date filter
        if ($request->has('arrival_date') && !empty($request->arrival_date)) {
            try {
                // Format comes as dd/mm/yyyy from frontend
                $date = Carbon::createFromFormat('d/m/Y', $request->arrival_date)->format('Y-m-d');
                $query->whereDate('arrival_date', $date);
            } catch (\Exception $e) {
                // Ignore invalid date format and just export all
            }
        }

        // Get data
        $historyItems = $query->get();

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set sheet title
        $sheet->setTitle('History Kedatangan Barang');

        // Set headers
        $headers = [
            'No',
            'Item Code',
            'Item Name',
            'Supplier Name',
            'Sched. Receipt Qty.',
            'PO No.',
            'Jumlah Item yang Datang',
            'Tanggal Kedatangan',
            'Pengiriman Tanggal',
        ];

        // Style for header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        // Set header row
        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $colIndex = 0;
        foreach ($headers as $header) {
            $colLetter = $columnLetters[$colIndex];
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
            $colIndex++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);

        // Set header row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Fill data
        $row = 2;
        $no = 1;
        foreach ($historyItems as $item) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $item->item_code ?? '-');
            $sheet->setCellValue('C' . $row, $item->item_name ?? '-');
            $sheet->setCellValue('D' . $row, $item->supplier_name ?? '-');
            $sheet->setCellValue('E' . $row, $item->scheduled_receipt_qty ?? 0);
            $sheet->setCellValue('F' . $row, $item->po_no ?? '-');
            $sheet->setCellValue('G' . $row, $item->arrived_qty ?? 0);

            // Format dates
            if ($item->arrival_date) {
                $sheet->setCellValue('H' . $row, Carbon::parse($item->arrival_date)->format('d/m/Y'));
            } else {
                $sheet->setCellValue('H' . $row, '-');
            }

            if ($item->pengiriman_tanggal) {
                $sheet->setCellValue('I' . $row, Carbon::parse($item->pengiriman_tanggal)->format('d/m/Y'));
            } else {
                $sheet->setCellValue('I' . $row, '-');
            }

            // Style for data rows
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center align for number columns
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Freeze first row
        $sheet->freezePane('A2');

        // Generate filename with timestamp
        $filename = 'History_Kedatangan_Barang_' . date('Ymd_His') . '.xlsx';

        // Create writer and save
        $writer = new Xlsx($spreadsheet);

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Save to php output
        $writer->save('php://output');
        exit;
    }
}
