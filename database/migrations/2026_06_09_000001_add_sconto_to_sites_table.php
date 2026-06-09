<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->unsignedInteger('sconto')->nullable()->after('valore');
            $table->string('tipo_sconto', 20)->nullable()->after('sconto');
            // valori: 'ricorrente' | 'primo_pagamento'
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['sconto', 'tipo_sconto']);
        });
    }
};
