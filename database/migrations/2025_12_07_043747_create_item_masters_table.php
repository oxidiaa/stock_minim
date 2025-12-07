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
        Schema::create('item_masters', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->integer('outstanding')->default(0);
            $table->integer('ending_balance')->default(0);
            $table->integer('maximal_stock')->default(0);
            $table->integer('order_point')->default(0);
            $table->integer('minimal_stock')->default(0);
            $table->string('user')->nullable();
            $table->string('outstanding_pp')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_masters');
    }
};
