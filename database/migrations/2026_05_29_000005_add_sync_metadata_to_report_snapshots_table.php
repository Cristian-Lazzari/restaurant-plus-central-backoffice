<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_snapshots', function (Blueprint $table) {
            $table->unsignedSmallInteger('http_status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('has_warnings')->default(false);
            $table->unsignedInteger('orders_total')->nullable();
            $table->bigInteger('orders_revenue')->nullable();
            $table->unsignedInteger('reservations_total')->nullable();
            $table->unsignedInteger('reservations_covers')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('report_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'http_status_code',
                'response_time_ms',
                'has_warnings',
                'orders_total',
                'orders_revenue',
                'reservations_total',
                'reservations_covers',
            ]);
        });
    }
};
