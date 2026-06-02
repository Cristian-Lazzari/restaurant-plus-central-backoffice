<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteReportSyncService;
use Illuminate\Console\Command;

class SyncReportsCommand extends Command
{
    protected $signature = 'reports:sync {--site=} {--from=} {--to=}';

    protected $description = 'Synchronize report snapshots from configured Restaurant Plus dashboards.';

    public function handle(SiteReportSyncService $syncService): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (! $this->validDateOption($from) || ! $this->validDateOption($to)) {
            $this->error('The --from and --to options must use YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($from && $to && $from > $to) {
            $this->error('The --from date must be before or equal to --to.');

            return self::FAILURE;
        }

        $sites = $this->option('site')
            ? Site::whereKey($this->option('site'))->get()
            : Site::where('active', true)->orderBy('name')->get();

        if ($sites->isEmpty()) {
            $this->warn('No sites found.');

            return self::SUCCESS;
        }

        $success         = 0;
        $failed          = 0;
        $explicitPeriod  = $from || $to; // true se l'utente ha passato --from/--to

        foreach ($sites as $site) {
            if ($explicitPeriod) {
                // Periodo manuale: usa quello passato da CLI per tutti i siti.
                $syncFrom = $from ?: null;
                $syncTo   = $to   ?: null;
                $reason   = 'manual';
            } else {
                // Periodo automatico: determina per ogni sito individualmente.
                $site->load('latestSnapshot');
                $period   = $syncService->determineSyncPeriod($site);
                $syncFrom = $period['from'];
                $syncTo   = $period['to'];
                $reason   = $period['reason'];
            }

            $result = $syncService->sync($site, $syncFrom, $syncTo);

            if ($result['ok']) {
                $success++;
                $apiVer = $result['snapshot']->api_version ?? '?';
                $this->info(
                    $site->name
                    . ': synced (api_version=' . $apiVer
                    . ', period=' . $syncFrom . '→' . $syncTo
                    . ', reason=' . $reason . ')'
                    . ' in ' . ($result['response_time_ms'] ?? '-') . ' ms.'
                );
            } else {
                $failed++;
                $code   = $result['code'] ?? 'ERROR';
                $status = isset($result['http_status_code']) && $result['http_status_code']
                    ? ' HTTP ' . $result['http_status_code']
                    : '';
                $this->error($site->name . ': ' . $code . $status . ' — ' . $result['message']);
            }
        }

        $this->line('Totale siti: ' . $sites->count() . '. Riusciti: ' . $success . '. Falliti: ' . $failed . '.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function validDateOption(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }
}
