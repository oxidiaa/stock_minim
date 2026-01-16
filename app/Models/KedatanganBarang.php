<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KedatanganBarang extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'supplier_name',
        'scheduled_receipt_qty',
        'po_no',
        'arrival_date',
        'arrived_qty',
        'pengiriman_tanggal',
        'po_validation',
        'imported_at',
        'request_whc',
        'request_whc_date',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'pengiriman_tanggal' => 'date',
        'imported_at' => 'datetime',
    ];
}
