<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_todolist_holes', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('slot_count');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('central_todolist_holes', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
