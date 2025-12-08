<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'outstanding',
        'ending_balance',
        'maximal_stock',
        'order_point',
        'minimal_stock',
        'user',
        'outstanding_pp',
        'note',
        'imported_at',
        'request_whc',
        'request_whc_edited_at',
        'sudah_follow',
        'sudah_follow_edited_at',
        'pengiriman_tanggal',
        'pengiriman_tanggal_edited_at',
        'qty_akan_dikirim',
        'selected_po_no',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'request_whc_edited_at' => 'datetime',
        'sudah_follow_edited_at' => 'datetime',
        'pengiriman_tanggal' => 'date',
        'pengiriman_tanggal_edited_at' => 'datetime',
    ];
}
