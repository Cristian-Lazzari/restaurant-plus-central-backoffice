<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('central_todolist_holes', function (Blueprint $table) {
            $table->tinyInteger('slot_count')->default(1)->after('insert_after');
        });
    }

    public function down(): void
    {
        Schema::table('central_todolist_holes', function (Blueprint $table) {
            $table->dropColumn('slot_count');
        });
    }
};
