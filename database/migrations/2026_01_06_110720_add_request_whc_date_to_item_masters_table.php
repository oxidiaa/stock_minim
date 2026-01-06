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
        Schema::table('item_masters', function (Blueprint $table) {
            $table->date('request_whc_date')->nullable()->after('request_whc');
            $table->timestamp('request_whc_date_edited_at')->nullable()->after('request_whc_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_masters', function (Blueprint $table) {
            $table->dropColumn(['request_whc_date', 'request_whc_date_edited_at']);
        });
    }
};
