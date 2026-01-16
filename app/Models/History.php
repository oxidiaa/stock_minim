<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class History extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_date',
        'item_code',
        'item_name',
        'supplier_name',
        'po_no',
        'scheduled_receipt_qty',
        'jumlah_item_datang',
        'pengiriman_tanggal',
        'edited_at',
        'request_whc',
        'request_whc_date',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'pengiriman_tanggal' => 'date',
        'edited_at' => 'datetime',
    ];
}
