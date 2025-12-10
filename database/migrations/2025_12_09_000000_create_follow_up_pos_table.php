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
        Schema::create('follow_up_pos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_master_id')->index();
            $table->string('po_no')->nullable();
            $table->integer('qty_akan_dikirim')->nullable();
            $table->date('pengiriman_tanggal')->nullable();
            $table->timestamps();

            $table->foreign('item_master_id')
                  ->references('id')->on('item_masters')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_pos');
    }
};


