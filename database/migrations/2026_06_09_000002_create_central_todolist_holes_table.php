<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_todolist_holes', function (Blueprint $table) {
            $table->id();
            $table->string('day_key', 20);          // es. "w1_2"
            $table->string('label', 200);            // es. "Visita medica"
            $table->string('time_label', 40)->nullable(); // es. "09:00–12:00"
            // -1 = prima di tutto, 0 = dopo blocco 0, 1 = dopo blocco 1, ...
            $table->smallInteger('insert_after')->default(-1);
            $table->timestamps();

            $table->index('day_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_todolist_holes');
    }
};
