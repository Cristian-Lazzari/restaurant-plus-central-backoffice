<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_pipeline_smm', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('citta', 100)->nullable();
            // Instagram / LinkedIn / TikTok / Multi
            $table->string('piattaforma', 30)->default('Instagram');
            $table->string('profilo')->nullable();
            $table->unsignedSmallInteger('ristoranti')->nullable();
            // nuovo / contattato / interessato / partner / rifiutato
            $table->string('stato', 20)->default('nuovo');
            $table->unsignedSmallInteger('fee')->nullable();
            $table->unsignedSmallInteger('clienti')->default(0);
            $table->date('data_contatto')->nullable();
            // Instagram DM / LinkedIn / Email / Telefono / WhatsApp
            $table->string('canale', 30)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('stato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_pipeline_smm');
    }
};
