<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_snapshots', function (Blueprint $table) {
            // Per-period counters estratti dal payload V2 (periods.today / last_7_days / last_30_days).
            // Nullable: i payload V1 storici non hanno la chiave "periods", quindi restano null.
            $table->unsignedInteger('orders_today')->nullable()->after('reservations_covers');
            $table->unsignedInteger('reservations_today')->nullable()->after('orders_today');
            $table->unsignedInteger('orders_last_7_days')->nullable()->after('reservations_today');
            $table->unsignedInteger('reservations_last_7_days')->nullable()->after('orders_last_7_days');
            $table->unsignedInteger('orders_last_30_days')->nullable()->after('reservations_last_7_days');
            $table->unsignedInteger('reservations_last_30_days')->nullable()->after('orders_last_30_days');
        });
    }

    public function down(): void
    {
        Schema::table('report_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'orders_today',
                'reservations_today',
                'orders_last_7_days',
                'reservations_last_7_days',
                'orders_last_30_days',
                'reservations_last_30_days',
            ]);
        });
    }
};
