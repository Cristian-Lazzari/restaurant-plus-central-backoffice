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
        $period = $this->validatedPeriod($request);
        $sites = Site::where('active', true)->orderBy('name')->get();

        if ($sites->count() > 5) {
            return back()->with(
                'error',
                'La sincronizzazione globale da interfaccia è limitata a 5 siti attivi. Usa php artisan reports:sync per sincronizzare tutti i siti.'
            );
        }

        $success = 0;
        $failed = 0;

        foreach ($sites as $site) {
            $result = $syncService->sync($site, $period['from'] ?? null, $period['to'] ?? null);
            $result['ok'] ? $success++ : $failed++;
        }

        $message = 'Sync completed. Success: ' . $success . '. Failed: ' . $failed . '.';

        return back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    private function validatedPeriod(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }
}
