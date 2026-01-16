<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kedatangan_barangs', function (Blueprint $table) {
            $table->integer('request_whc')->default(0)->after('scheduled_receipt_qty');
            $table->date('request_whc_date')->nullable()->after('request_whc');
        });

        Schema::table('histories', function (Blueprint $table) {
            $table->integer('request_whc')->default(0)->after('scheduled_receipt_qty');
            $table->date('request_whc_date')->nullable()->after('request_whc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kedatangan_barangs', function (Blueprint $table) {
            $table->dropColumn(['request_whc', 'request_whc_date']);
        });

        Schema::table('histories', function (Blueprint $table) {
            $table->dropColumn(['request_whc', 'request_whc_date']);
        });
    }
};
