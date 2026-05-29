<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_errors', function (Blueprint $table) {
            $table->string('code')->nullable()->index();
            $table->unsignedSmallInteger('http_status_code')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('sync_errors', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['http_status_code']);
            $table->dropColumn(['code', 'http_status_code']);
        });
    }
};
