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

        foreach ($sites as $site) {
            // Passa sempre senza from/to: il payload V2 calcola i periods fissi
            // in modo indipendente dal range — ogni sync è già completa.
            $result = $syncService->sync($site);
            $result['ok'] ? $success++ : $failed++;
        }

        $total   = $sites->count();
        $message = "Sync completata: {$total} siti. Riusciti: {$success}. Falliti: {$failed}.";

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
