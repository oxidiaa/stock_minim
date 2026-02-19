<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUpPO extends Model
{
    use HasFactory;

    protected $table = 'follow_up_pos';

    protected $fillable = [
        'item_master_id',
        'po_no',
        'sudah_follow',
        'qty_akan_dikirim',
        'pengiriman_tanggal',
    ];

    protected $casts = [
        'pengiriman_tanggal' => 'date',
    ];
}













