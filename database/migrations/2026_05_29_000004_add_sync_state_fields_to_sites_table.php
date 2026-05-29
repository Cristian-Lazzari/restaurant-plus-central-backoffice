<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->tinyInteger('pack')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('retention_days')->nullable()->default(90);
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'pack',
                'consecutive_failures',
                'notes',
                'retention_days',
            ]);
        });
    }
};
