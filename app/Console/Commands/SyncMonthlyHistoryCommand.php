<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteReportSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

class SyncMonthlyHistoryCommand extends Command
{
    protected $signature = 'reports:sync-monthly-history
                            {--site= : ID del sito, ometti per tutti i siti attivi}
                            {--from=2000-01 : Mese iniziale in formato YYYY-MM}
                            {--to= : Mese finale in formato YYYY-MM, default mese corrente}
                            {--refresh : Riscarica anche i mesi già presenti}
                            {--force : Salta la richiesta di conferma}';

    protected $description = 'Sincronizza lo storico mese per mese per alimentare la tabella mensile del dettaglio sito.';

    public function handle(SiteReportSyncService $syncService): int
    {
        $sites = $this->option('site')
            ? Site::query()->whereKey($this->option('site'))->get()
            : Site::query()->where('active', true)->orderBy('name')->get();

        if ($sites->isEmpty()) {
            $this->error($this->option('site') ? 'Sito non trovato: ' . $this->option('site') : 'Nessun sito attivo trovato.');

            return self::FAILURE;
        }

        try {
            $from = $this->monthFromOption((string) $this->option('from'), 'from');
            $to = $this->option('to')
                ? $this->monthFromOption((string) $this->option('to'), 'to')
                : CarbonImmutable::today()->startOfMonth();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($from->gt($to)) {
            $this->error('Il mese iniziale deve essere precedente o uguale al mese finale.');

            return self::FAILURE;
        }

        $months = $this->monthsBetween($from, $to);
        $totalRequests = count($months) * $sites->count();

        $this->warn("Sto per controllare {$sites->count()} siti per " . count($months) . " mesi ({$totalRequests} richieste massime).");

        if (! $this->option('force') && ! $this->confirm('Vuoi continuare?')) {
            $this->info('Operazione annullata.');

            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($sites as $site) {
            $this->line('Sito: ' . $site->name);

            foreach ($months as $month) {
                $periodFrom = $month->startOfMonth()->toDateString();
                $periodTo = $month->endOfMonth()->min(CarbonImmutable::today())->toDateString();

                $existingQuery = $site->reportSnapshots()
                    ->whereDate('period_from', $periodFrom)
                    ->whereDate('period_to', $periodTo);

                if ($existingQuery->exists() && ! $this->option('refresh')) {
                    $skipped++;
                    continue;
                }

                if ($this->option('refresh')) {
                    $existingQuery->delete();
                }

                $result = $syncService->sync($site, $periodFrom, $periodTo);

                if ($result['ok']) {
                    $success++;
                    continue;
                }

                $failed++;
                $this->warn($month->format('Y-m') . ': ' . ($result['code'] ?? 'ERROR') . ' - ' . ($result['message'] ?? 'Sync failed'));
            }
        }

        $this->info("Storico mensile completato. Riusciti: {$success}. Saltati: {$skipped}. Falliti: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function monthFromOption(string $value, string $name): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value) !== 1) {
            $this->error("Opzione --{$name} non valida. Usa il formato YYYY-MM.");
            throw new InvalidArgumentException("Opzione --{$name} non valida. Usa il formato YYYY-MM.");
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value . '-01')->startOfMonth();
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function monthsBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = [];
        $cursor = $from->startOfMonth();
        $last = $to->startOfMonth();

        while ($cursor->lte($last)) {
            $months[] = $cursor;
            $cursor = $cursor->addMonth();
        }

        return $months;
    }
}
