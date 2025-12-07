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
        Schema::table('item_masters', function (Blueprint $table) {
            $table->string('sudah_follow')->nullable()->after('note');
            $table->timestamp('sudah_follow_edited_at')->nullable()->after('sudah_follow');
            $table->date('pengiriman_tanggal')->nullable()->after('sudah_follow_edited_at');
            $table->timestamp('pengiriman_tanggal_edited_at')->nullable()->after('pengiriman_tanggal');
            $table->integer('qty_akan_dikirim')->nullable()->after('pengiriman_tanggal_edited_at');
            $table->string('selected_po_no')->nullable()->after('qty_akan_dikirim');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_masters', function (Blueprint $table) {
            $table->dropColumn([
                'sudah_follow',
                'sudah_follow_edited_at',
                'pengiriman_tanggal',
                'pengiriman_tanggal_edited_at',
                'qty_akan_dikirim',
                'selected_po_no'
            ]);
        });
    }
};
