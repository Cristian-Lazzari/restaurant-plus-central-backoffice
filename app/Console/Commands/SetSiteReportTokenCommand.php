<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

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

        DB::table('sites')
            ->where('id', $site->id)
            ->update([
                'token' => Crypt::encryptString($token),
                'consecutive_failures' => 0,
                'last_error_at' => null,
                'updated_at' => now(),
            ]);

        $site->forceFill([
            'consecutive_failures' => 0,
            'last_error_at' => null,
        ])->syncOriginal();

        if (Site::find($site->id)?->token !== $token) {
            $this->error('Token salvato ma la verifica di lettura non coincide.');

            return self::FAILURE;
        }

        $this->info('Token aggiornato per: ' . $site->name);

        return self::SUCCESS;
    }
}
