<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Command;

class RotateSiteReportTokensCommand extends Command
{
    protected $signature = 'sites:rotate-report-tokens
                            {--site= : ID del sito specifico}
                            {--all : Ruota i token di tutti i siti}
                            {--force : Salta la richiesta di conferma}';

    protected $description = 'Rigenera i token di report privato salvati sui siti e stampa i valori da copiare nelle dashboard sorgente.';

    public function handle(): int
    {
        $siteId = $this->option('site');
        $all = (bool) $this->option('all');

        if (($siteId && $all) || (! $siteId && ! $all)) {
            $this->error('Usa --site=ID per un sito specifico oppure --all per tutti i siti.');

            return self::FAILURE;
        }

        $sites = $siteId
            ? Site::query()->whereKey($siteId)->get()
            : Site::query()->orderBy('name')->get();

        if ($sites->isEmpty()) {
            $this->error($siteId ? 'Sito non trovato: ' . $siteId : 'Nessun sito trovato.');

            return self::FAILURE;
        }

        $this->warn('I token esistenti verranno sostituiti nel backoffice centrale.');
        $this->warn('Dopo il comando dovrai copiare ogni nuovo PRIVATE_REPORT_TOKEN nel .env della relativa dashboard sorgente.');

        if (! $this->option('force') && ! $this->confirm('Vuoi continuare?')) {
            $this->info('Operazione annullata.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($sites as $site) {
            $token = bin2hex(random_bytes(32));

            $site->forceFill([
                'token' => $token,
                'consecutive_failures' => 0,
                'last_error_at' => null,
            ])->save();

            $rows[] = [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'private_report_token' => $token,
            ];
        }

        $this->info('Token rigenerati. Copia questi valori nei .env delle dashboard sorgente:');
        $this->newLine();

        foreach ($rows as $row) {
            $this->line('Sito #' . $row['id'] . ' - ' . $row['name']);
            $this->line('URL: ' . $row['url']);
            $this->line('PRIVATE_REPORT_TOKEN=' . $row['private_report_token']);
            $this->line('PRIVATE_REPORT_REVENUE_UNIT=euros');
            $this->newLine();
        }

        $this->warn('Questi token sono segreti: non condividerli e non committarli.');
        $this->info('Dopo aver aggiornato i .env sorgente, esegui su ogni dashboard sorgente: php artisan optimize:clear && php artisan config:cache');

        return self::SUCCESS;
    }
}
