<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPO extends Model
{
    use HasFactory;

    protected $table = 'data_pos';

    protected $fillable = [
        'item_code',
        'item_name',
        'supplier_name',
        'scheduled_receipt_qty',
        'po_no',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];
}
