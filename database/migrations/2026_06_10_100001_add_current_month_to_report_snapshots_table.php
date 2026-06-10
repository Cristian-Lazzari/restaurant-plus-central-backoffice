<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('orders_current_month')->nullable()->after('orders_last_30_days');
            $table->unsignedBigInteger('reservations_current_month')->nullable()->after('reservations_last_30_days');
        });
    }

    public function down(): void
    {
        Schema::table('report_snapshots', function (Blueprint $table) {
            $table->dropColumn(['orders_current_month', 'reservations_current_month']);
        });
    }
};
