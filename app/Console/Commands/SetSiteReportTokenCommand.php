<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Command;

class SetSiteReportTokenCommand extends Command
{
    protected $signature = 'sites:set-report-token
                            {site : ID del sito nel backoffice centrale}
                            {token : PRIVATE_REPORT_TOKEN letto dalla dashboard sorgente}';

    protected $description = 'Reinserisce il token report di un sito cifrandolo con l APP_KEY attuale.';

    public function handle(): int
    {
        $site = Site::find($this->argument('site'));

        if (! $site) {
            $this->error('Sito non trovato: ' . $this->argument('site'));

            return self::FAILURE;
        }

        $token = trim((string) $this->argument('token'));

        if ($token === '') {
            $this->error('Token vuoto.');

            return self::FAILURE;
        }

        $site->forceFill([
            'token' => $token,
            'consecutive_failures' => 0,
            'last_error_at' => null,
        ])->save();

        $this->info('Token aggiornato per: ' . $site->name);

        return self::SUCCESS;
    }
}
