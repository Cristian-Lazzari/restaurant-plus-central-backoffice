<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_pipeline_leads', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('ristorante')->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            // smm / ads / referral / organico / webinar / diretto
            $table->string('fonte', 20)->default('diretto');
            $table->string('smm_ref')->nullable();
            // nuovo / contattato / interessato / demo / proposta / followup / chiuso / perso
            $table->string('stato', 20)->default('nuovo');
            // alta / media / bassa
            $table->string('priorita', 10)->default('bassa');
            // base / inter / top
            $table->string('pacchetto', 10)->nullable();
            $table->unsignedSmallInteger('valore')->nullable();
            $table->date('data_contatto')->nullable();
            $table->date('followup_date')->nullable();
            $table->string('nextstep')->nullable();
            $table->text('note')->nullable();
            // sicuro / rischio (tag interno)
            $table->string('tag', 20)->nullable();
            $table->timestamps();

            $table->index('stato');
            $table->index('priorita');
            $table->index('fonte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_pipeline_leads');
    }
};
