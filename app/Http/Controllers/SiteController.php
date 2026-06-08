<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\BackofficeSettingsService;
use App\Services\SiteMonthlyMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteController extends Controller
{
    public function index(SiteMonthlyMetricsService $monthlyMetrics, BackofficeSettingsService $settings)
    {
        $savingsBenchmark = $settings->savingsBenchmark();
        $monthlyMetrics->useSavingsBenchmark(
            $savingsBenchmark['order_commission_rate'],
            $savingsBenchmark['reservation_cover_fee']
        );

        $canReorderSites = Schema::hasColumn('sites', 'sort_order');

        $sitesQuery = Site::connected()->with(['latestSnapshot', 'latestError']);

        if ($canReorderSites) {
            $sitesQuery
                ->orderByRaw('sort_order is null')
                ->orderBy('sort_order');
        }

        $sites = $sitesQuery
            ->orderBy('name')
            ->get();

        $siteMetrics = [];
        $lastGlobalSyncAt = $sites->reduce(function ($latest, Site $site) {
            if (! $site->last_success_at) {
                return $latest;
            }

            return $latest === null || $site->last_success_at->gt($latest)
                ? $site->last_success_at
                : $latest;
        });

        // ── KPI globali — iterazione sulla collection già caricata, zero query aggiuntive ──
        $activeCount = 0;
        $sitesWithFailures = 0;

        // All-time aggregati (da payload.periods.all_time, disponibili solo con V2)
        $allTimeOrders = 0;
        $allTimeRevenue = 0.0;
        $allTimeReservations = 0;
        $allTimeCovers = 0;
        $estimatedOrderSavings = 0.0;
        $estimatedReservationSavings = 0.0;

        // Medie mensili globali = somma delle medie mensili per sito
        $monthlyAvgOrders = 0.0;
        $monthlyAvgRevenue = 0.0;
        $monthlyAvgReservations = 0.0;
        $monthlyAvgCovers = 0.0;

        // Per il grafico e i dati "oggi / 7gg"
        $ordersTodayTotal = 0;
        $reservationsTodayTotal = 0;
        $ordersLast7Total = 0;
        $reservationsLast7Total = 0;

        foreach ($sites as $site) {
            if ($site->active) {
                $activeCount++;
            }
            if ($site->consecutive_failures > 0) {
                $sitesWithFailures++;
            }

            $snap = $site->latestSnapshot;
            $metrics = $monthlyMetrics->forSnapshot($snap);
            $siteMetrics[$site->id] = $metrics;

            if (! $snap) {
                continue;
            }

            if ($metrics['has_all_time']) {
                $allTimeOrders += $metrics['orders_total'];
                $allTimeRevenue += $metrics['orders_revenue'] ?? 0.0;
                $allTimeReservations += $metrics['reservations_total'];
                $allTimeCovers += $metrics['reservations_covers'];
                $estimatedOrderSavings += $metrics['estimated_order_savings'] ?? 0.0;
                $estimatedReservationSavings += $metrics['estimated_reservation_savings'] ?? 0.0;

                if ($metrics['orders_monthly_avg'] !== null) {
                    $monthlyAvgOrders += $metrics['orders_monthly_avg'];
                }
                if ($metrics['revenue_monthly_avg'] !== null) {
                    $monthlyAvgRevenue += $metrics['revenue_monthly_avg'];
                }
                if ($metrics['reservations_monthly_avg'] !== null) {
                    $monthlyAvgReservations += $metrics['reservations_monthly_avg'];
                }
                if ($metrics['covers_monthly_avg'] !== null) {
                    $monthlyAvgCovers += $metrics['covers_monthly_avg'];
                }
            }

            $ordersTodayTotal += (int) ($snap->orders_today ?? 0);
            $reservationsTodayTotal += (int) ($snap->reservations_today ?? 0);
            $ordersLast7Total += (int) ($snap->orders_last_7_days ?? 0);
            $reservationsLast7Total += (int) ($snap->reservations_last_7_days ?? 0);
        }

        $kpis = [
            'active_count' => $activeCount,
            'sites_with_failures' => $sitesWithFailures,
            // All-time
            'orders_all_time' => $allTimeOrders,
            'revenue_all_time' => $allTimeRevenue,
            'reservations_all_time' => $allTimeReservations,
            'covers_all_time' => $allTimeCovers,
            'estimated_order_savings' => round($estimatedOrderSavings, 2),
            'estimated_reservation_savings' => round($estimatedReservationSavings, 2),
            'estimated_total_savings' => round($estimatedOrderSavings + $estimatedReservationSavings, 2),
            // Medie mensili (somma delle medie per sito)
            // null = active_months non ancora nel payload → serve nuova sync
            'orders_monthly_avg' => $monthlyAvgOrders > 0 ? round($monthlyAvgOrders) : null,
            'revenue_monthly_avg' => $monthlyAvgRevenue > 0 ? round($monthlyAvgRevenue, 2) : null,
            'reservations_monthly_avg' => $monthlyAvgReservations > 0 ? round($monthlyAvgReservations) : null,
            'covers_monthly_avg' => $monthlyAvgCovers > 0 ? round($monthlyAvgCovers) : null,
            // Oggi / 7gg per grafico
            'orders_today' => $ordersTodayTotal,
            'reservations_today' => $reservationsTodayTotal,
            'orders_last_7' => $ordersLast7Total,
            'reservations_last_7' => $reservationsLast7Total,
            // Flag: nessun dato ancora (snapshot V2 non ancora arrivato)
            'has_v2_data' => $allTimeOrders > 0 || $allTimeReservations > 0,
            // Flag servizi non utilizzati (globalmente)
            'uses_orders' => $allTimeOrders > 0,
            'uses_reservations' => $allTimeReservations > 0,
        ];

        // ── Inactivity detection ──────────────────────────────────────────────
        // Identify sites with no menu activity in the last 30 days (max 5 shown).
        $thirtyDaysAgo = now()->subDays(30);
        $inactiveSites = [];

        foreach ($sites as $site) {
            $snap = $site->latestSnapshot;

            // No snapshot at all.
            if (! $snap) {
                $inactiveSites[] = [
                    'site' => $site,
                    'reason' => 'no_snapshot',
                    'last_activity' => null,
                ];

                continue;
            }

            // Snapshot V1: no usage block.
            $usageMenu = $snap->payload['usage']['menu'] ?? null;

            if ($usageMenu === null) {
                if (($snap->api_version ?? '1') !== '2') {
                    $inactiveSites[] = [
                        'site' => $site,
                        'reason' => 'no_v2',
                        'last_activity' => null,
                    ];
                }

                continue;
            }

            // Find most recent menu date.
            $rawDates = array_filter([
                $usageMenu['last_product_updated_at'] ?? null,
                $usageMenu['last_category_updated_at'] ?? null,
                $usageMenu['last_ingredient_updated_at'] ?? null,
            ]);

            if (empty($rawDates)) {
                $inactiveSites[] = [
                    'site' => $site,
                    'reason' => 'no_menu_activity',
                    'last_activity' => null,
                ];

                continue;
            }

            $lastActivityRaw = max($rawDates);

            try {
                if (\Carbon\Carbon::parse($lastActivityRaw)->lt($thirtyDaysAgo)) {
                    $inactiveSites[] = [
                        'site' => $site,
                        'reason' => 'stale_menu',
                        'last_activity' => $lastActivityRaw,
                    ];
                }
            } catch (\Throwable $e) {
                // Unparsable date — skip silently.
            }
        }

        // Sort: no_snapshot first, then no_v2, then no_menu_activity, then stale.
        $reasonOrder = ['no_snapshot' => 0, 'no_v2' => 1, 'no_menu_activity' => 2, 'stale_menu' => 3];
        usort($inactiveSites, fn ($a, $b) => ($reasonOrder[$a['reason']] ?? 9) <=> ($reasonOrder[$b['reason']] ?? 9));
        $inactiveSites = array_slice($inactiveSites, 0, 5);

        return view('sites.index', compact('sites', 'kpis', 'inactiveSites', 'siteMetrics', 'lastGlobalSyncAt', 'canReorderSites', 'savingsBenchmark'));
    }

    public function create()
    {
        return view('sites.create', ['site' => new Site]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048', 'starts_with:https://'],
            'token' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ], $this->validationMessages());

        $data['url'] = rtrim($data['url'], '/');
        $data['active'] = $request->boolean('active');
        $data['retention_days'] = $data['retention_days'] ?? 90;
        if (Schema::hasColumn('sites', 'sort_order')) {
            $data['sort_order'] = ((int) (Site::max('sort_order') ?? Site::count())) + 1;
        }

        Site::create($data);

        return redirect()->route('dashboard')->with('success', 'Site created.');
    }

    public function show(Site $site, SiteMonthlyMetricsService $monthlyMetrics, BackofficeSettingsService $settings)
    {
        $savingsBenchmark = $settings->savingsBenchmark();
        $monthlyMetrics->useSavingsBenchmark(
            $savingsBenchmark['order_commission_rate'],
            $savingsBenchmark['reservation_cover_fee']
        );

        $site->load([
            'latestSnapshot',
            'reportSnapshots' => fn ($query) => $query->orderBy('period_from')->orderBy('fetched_at'),
            'syncErrors' => fn ($query) => $query->latest('occurred_at')->limit(10),
        ]);

        $businessMetrics = $monthlyMetrics->forSnapshot($site->latestSnapshot);
        $usesOrders = (int) ($businessMetrics['orders_total'] ?? 0) > 0
            || (int) ($site->latestSnapshot?->orders_total ?? 0) > 0;
        $usesReservations = (int) ($businessMetrics['reservations_total'] ?? 0) > 0
            || (int) ($site->latestSnapshot?->reservations_total ?? 0) > 0;
        $monthlyTrend = $monthlyMetrics->monthlyTrendForSnapshots($site->reportSnapshots, $site->latestSnapshot);

        return view('sites.show', compact('site', 'businessMetrics', 'usesOrders', 'usesReservations', 'monthlyTrend', 'savingsBenchmark'));
    }

    public function edit(Site $site)
    {
        return view('sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048', 'starts_with:https://'],
            'token' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ], $this->validationMessages());

        $site->name = $data['name'];
        $site->url = rtrim($data['url'], '/');
        $site->active = $request->boolean('active');
        $site->notes = $data['notes'] ?? null;
        $site->retention_days = $data['retention_days'] ?? 90;

        if (! empty($data['token'])) {
            $site->token = $data['token'];
        }

        $site->save();

        return redirect()->route('sites.show', $site)->with('success', 'Site updated.');
    }

    public function toggle(Site $site)
    {
        $site->update(['active' => ! $site->active]);

        return back()->with('success', $site->active ? 'Site activated.' : 'Site deactivated.');
    }

    public function reorder(Request $request)
    {
        if (! Schema::hasColumn('sites', 'sort_order')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Esegui le migration per abilitare il riordino siti.');
        }

        $data = $request->validate([
            'site_ids' => ['required', 'array', 'min:1'],
            'site_ids.*' => ['required', 'integer', 'distinct', 'exists:sites,id'],
        ]);

        DB::transaction(function () use ($data): void {
            foreach (array_values($data['site_ids']) as $index => $siteId) {
                Site::whereKey($siteId)->update(['sort_order' => $index + 1]);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Ordine siti aggiornato.');
    }

    private function validationMessages(): array
    {
        return [
            'url.starts_with' => 'The dashboard URL must start with https://.',
        ];
    }

}
