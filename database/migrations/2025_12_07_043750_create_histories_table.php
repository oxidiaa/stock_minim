<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->date('arrival_date');
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('supplier_name')->nullable();
            $table->string('po_no')->nullable();
            $table->integer('scheduled_receipt_qty')->default(0);
            $table->integer('jumlah_item_datang')->default(0);
            $table->date('pengiriman_tanggal')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
