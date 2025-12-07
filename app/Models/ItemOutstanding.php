<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemOutstanding extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_date',
        'item_code',
        'item_name',
        'user',
        'outstanding',
        'sudah_pp',
        'outstanding_pp',
        'ending_balance',
        'maximal_stock',
        'order_point',
        'minimal_stock',
        'note',
        'duplicate_note',
        'sudah_follow',
        'sudah_follow_edited_at',
        'pengiriman_tanggal',
        'pengiriman_tanggal_edited_at',
        'qty_akan_dikirim',
        'selected_po_no',
        'request_whc',
        'request_whc_edited_at',
        'imported_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'pengiriman_tanggal' => 'date',
        'sudah_follow_edited_at' => 'datetime',
        'pengiriman_tanggal_edited_at' => 'datetime',
        'request_whc_edited_at' => 'datetime',
        'imported_at' => 'datetime',
    ];
}
