<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role', 20)->default('restaurant'); // admin | restaurant
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        // Bootstrap account CEO dalle credenziali legacy in .env,
        // così il login esistente continua a funzionare dopo la migration.
        $username = (string) config('backoffice.username');
        $password = (string) config('backoffice.password');

        if ($username !== '' && $password !== '') {
            DB::table('users')->insert([
                'name' => 'CEO',
                'username' => $username,
                'password' => Hash::make($password),
                'role' => 'admin',
                'site_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
