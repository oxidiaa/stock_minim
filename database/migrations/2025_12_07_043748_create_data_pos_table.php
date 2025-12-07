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
        Schema::create('data_pos', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('supplier_name')->nullable();
            $table->integer('scheduled_receipt_qty')->default(0);
            $table->string('po_no')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pos');
    }
};
