<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->string('api_version')->nullable();
            $table->string('revenue_unit')->nullable();
            $table->json('payload');
            $table->json('data_warnings')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['site_id', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
    }
};
