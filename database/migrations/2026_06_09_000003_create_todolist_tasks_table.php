<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todolist_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('week_id', 10);
            $table->tinyInteger('day_index');
            $table->tinyInteger('block_index');
            $table->smallInteger('sort_order');
            $table->text('text');
            $table->string('tag', 20)->default('ops');
            $table->boolean('is_done')->default(false);
            $table->string('original_week_id', 10)->nullable();
            $table->tinyInteger('original_day_index')->nullable();
            $table->tinyInteger('original_block_index')->nullable();
            $table->timestamps();

            $table->index(['week_id', 'day_index', 'block_index', 'sort_order'], 'idx_task_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todolist_tasks');
    }
};
