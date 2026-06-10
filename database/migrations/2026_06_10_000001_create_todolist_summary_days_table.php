<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todolist_summary_days', function (Blueprint $table) {
            $table->id();
            $table->string('week_id', 10);
            $table->string('week_label', 50);
            $table->string('week_color', 15)->default('#6366f1');
            $table->tinyInteger('week_month');
            $table->string('week_dates', 30);
            $table->text('week_subtitle')->nullable();
            $table->string('week_focus', 200)->nullable();
            $table->json('week_goals')->nullable();
            $table->tinyInteger('day_index');
            $table->string('day_name', 20);
            $table->text('day_theme')->nullable();
            $table->string('day_hours', 10)->nullable();
            $table->date('calendar_date')->nullable();
            $table->timestamps();

            $table->unique(['week_id', 'day_index']);
            $table->index('calendar_date');
            $table->index('week_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todolist_summary_days');
    }
};
