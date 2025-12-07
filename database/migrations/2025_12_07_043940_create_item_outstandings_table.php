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
        Schema::create('item_outstandings', function (Blueprint $table) {
            $table->id();
            $table->date('request_date')->nullable();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('user')->nullable();
            $table->integer('outstanding')->default(0);
            $table->integer('sudah_pp')->default(0);
            $table->string('outstanding_pp')->nullable();
            $table->integer('ending_balance')->default(0);
            $table->integer('maximal_stock')->default(0);
            $table->integer('order_point')->default(0);
            $table->integer('minimal_stock')->default(0);
            $table->text('note')->nullable();
            $table->string('duplicate_note')->nullable();
            
            // Follow up fields
            $table->string('sudah_follow')->nullable(); // YES/NO
            $table->timestamp('sudah_follow_edited_at')->nullable();
            
            $table->date('pengiriman_tanggal')->nullable();
            $table->timestamp('pengiriman_tanggal_edited_at')->nullable();
            
            $table->integer('qty_akan_dikirim')->nullable();
            $table->string('selected_po_no')->nullable();
            
            $table->integer('request_whc')->nullable();
            $table->timestamp('request_whc_edited_at')->nullable();
            
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_outstandings');
    }
};
