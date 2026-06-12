<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_plan_id')->constrained('marketing_plans')->cascadeOnDelete();
            $table->string('type', 20)->index();      // post | storia | video | promo | campagna | automazione | modello
            $table->string('code', 20);               // P-1, S-3, V-2, PR-1, C-1, A-1, M-1
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();      // campi specifici per tipo (foto, script, trigger, ...)
            $table->unsignedTinyInteger('week')->nullable();      // 1..n — posizione calendario
            $table->unsignedTinyInteger('day_index')->nullable(); // 0=Lun .. 6=Dom
            $table->string('slot', 12)->nullable();               // mattina | pomeriggio
            $table->boolean('completed')->default(false);
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['marketing_plan_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_items');
    }
};
