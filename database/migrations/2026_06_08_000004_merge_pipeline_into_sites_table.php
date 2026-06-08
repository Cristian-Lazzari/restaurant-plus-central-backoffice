<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('is_prospect')->default(false)->after('sort_order');
            $table->string('citta', 100)->nullable()->after('is_prospect');
            $table->string('telefono', 30)->nullable()->after('citta');
            $table->string('email', 150)->nullable()->after('telefono');
            $table->string('fonte', 20)->nullable()->after('email');
            $table->string('smm_ref', 200)->nullable()->after('fonte');
            $table->string('stato', 20)->nullable()->after('smm_ref');
            $table->string('priorita', 10)->nullable()->after('stato');
            $table->unsignedSmallInteger('valore')->nullable()->after('priorita');
            $table->date('data_contatto')->nullable()->after('valore');
            $table->date('followup_date')->nullable()->after('data_contatto');
            $table->string('nextstep', 500)->nullable()->after('followup_date');
            $table->string('tag', 20)->nullable()->after('nextstep');
        });

        Schema::dropIfExists('central_pipeline_leads');
    }

    public function down(): void
    {
        // Ricrea la tabella central_pipeline_leads (struttura minima per rollback)
        Schema::create('central_pipeline_leads', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 200);
            $table->string('ristorante', 200)->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('fonte', 20)->nullable();
            $table->string('smm_ref', 200)->nullable();
            $table->string('stato', 20)->nullable();
            $table->string('priorita', 10)->nullable();
            $table->string('pacchetto', 10)->nullable();
            $table->unsignedSmallInteger('valore')->nullable();
            $table->date('data_contatto')->nullable();
            $table->date('followup_date')->nullable();
            $table->string('nextstep', 500)->nullable();
            $table->text('note')->nullable();
            $table->string('tag', 20)->nullable();
            $table->timestamps();
        });

        // Non c'è un modo semplice di rimuovere colonne in SQLite senza ricreare la tabella,
        // quindi il down() ricrea solo central_pipeline_leads. Le colonne aggiunte a sites
        // restano (non si usa ->dropColumn in SQLite senza Doctrine DBAL).
    }
};
