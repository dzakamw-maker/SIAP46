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
        Schema::table('daily_recaps', function (Blueprint $table) {
            $table->decimal('opening_bni_balance', 15, 2)->nullable()->after('recap_date');
            $table->decimal('opening_cash_balance', 15, 2)->default(0)->after('opening_bni_balance');
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete()->after('recorded_by');
            $table->boolean('is_closed')->default(false)->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_recaps', function (Blueprint $table) {
            $table->dropForeign(['opened_by']);
            $table->dropColumn(['opening_bni_balance', 'opening_cash_balance', 'opened_by', 'is_closed']);
        });
    }
};
