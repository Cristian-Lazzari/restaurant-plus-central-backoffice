<?php

namespace App\Console\Commands;

use App\Models\ReportSnapshot;
use App\Models\Site;
use Illuminate\Console\Command;

class ClearSnapshotsCommand extends Command
{
    protected $signature = 'reports:clear-snapshots
                            {--site= : ID del sito (ometti per tutti i siti)}
                            {--force : Salta la richiesta di conferma}';

    protected $description = 'Svuota gli snapshot salvati. Il prossimo sync ripartirà dal primo dato disponibile.';

    public function handle(): int
    {
        if ($this->option('site')) {
            $site = Site::find($this->option('site'));

            if (! $site) {
                $this->error('Sito non trovato: ' . $this->option('site'));
                return self::FAILURE;
            }

            $count = $site->reportSnapshots()->count();

            if ($count === 0) {
                $this->warn($site->name . ': nessuno snapshot da rimuovere.');
                return self::SUCCESS;
            }

            if (! $this->option('force') && ! $this->confirm(
                "Rimuovere {$count} snapshot per \"{$site->name}\"? Il prossimo sync ripartirà dall'inizio."
            )) {
                $this->info('Operazione annullata.');
                return self::SUCCESS;
            }

            $site->reportSnapshots()->delete();
            $this->info("{$site->name}: rimossi {$count} snapshot.");
            return self::SUCCESS;
        }

        // Tutti i siti
        $total = ReportSnapshot::count();

        if ($total === 0) {
            $this->warn('Nessuno snapshot da rimuovere.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Rimuovere TUTTI i {$total} snapshot da tutti i siti? Il prossimo sync ripartirà dall'inizio per ogni sito."
        )) {
            $this->info('Operazione annullata.');
            return self::SUCCESS;
        }

        ReportSnapshot::truncate();
        $this->info("Rimossi {$total} snapshot. I prossimi sync ripartiranno dal primo dato disponibile.");

        return self::SUCCESS;
    }
}
