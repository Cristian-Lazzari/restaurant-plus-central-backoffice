<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_todolist_completions', function (Blueprint $table) {
            $table->id();
            // Formato chiave: "{week_id}_{day_index}_{block_index}_{task_index}"
            $table->string('task_key', 80)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_todolist_completions');
    }
};
