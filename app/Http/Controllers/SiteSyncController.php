<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SiteReportSyncService;
use Illuminate\Http\Request;

class SiteSyncController extends Controller
{
    public function sync(Request $request, Site $site, SiteReportSyncService $syncService)
    {
        $period = $this->validatedPeriod($request);
        $result = $syncService->sync($site, $period['from'] ?? null, $period['to'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $site->name . ': ' . $result['message']);
    }

    public function syncAll(Request $request, SiteReportSyncService $syncService)
    {
        $sites   = Site::where('active', true)->orderBy('name')->get();
        $success = 0;
        $failed  = 0;
        $failureSummaries = [];

        foreach ($sites as $site) {
            // Carica la relazione latestSnapshot per determineSyncPeriod().
            $site->load('latestSnapshot');
            $period = $syncService->determineSyncPeriod($site);
            $result = $syncService->sync($site, $period['from'], $period['to']);

            if ($result['ok']) {
                $success++;
            } else {
                $failed++;

                if (count($failureSummaries) < 5) {
                    $failureSummaries[] = $site->name . ': ' . $this->failureLabel($result);
                }
            }
        }

        $total   = $sites->count();
        $message = "Sync completata: {$total} siti. Riusciti: {$success}. Falliti: {$failed}.";

        if ($failed > 0 && count($failureSummaries) > 0) {
            $message .= ' Prime cause: ' . implode(' | ', $failureSummaries);
        }

        return back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    private function validatedPeriod(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }

    private function failureLabel(array $result): string
    {
        $parts = array_filter([
            $result['code'] ?? null,
            isset($result['http_status_code']) && $result['http_status_code'] !== null
                ? 'HTTP ' . $result['http_status_code']
                : null,
            $result['message'] ?? null,
        ]);

        return implode(' - ', $parts);
    }
}
