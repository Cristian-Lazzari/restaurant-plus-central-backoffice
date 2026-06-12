<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->unique()->constrained('sites')->cascadeOnDelete();
            $table->text('objective')->nullable();
            $table->string('timeline_label')->nullable();      // es. "4 settimane"
            $table->unsignedTinyInteger('weeks')->default(4);  // settimane del calendario
            $table->date('start_date')->nullable();            // lunedì settimana 1
            $table->json('social_status')->nullable();         // {instagram, facebook, tiktok, smm}
            $table->unsignedSmallInteger('photos_needed')->nullable();
            $table->unsignedSmallInteger('reels_needed')->nullable();
            $table->json('kpis')->nullable();                  // {clienti_online, consenso, tot_ordini, tot_prenotazioni}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_plans');
    }
};
