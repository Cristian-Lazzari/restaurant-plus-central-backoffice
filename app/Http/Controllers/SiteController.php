<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::with(['latestSnapshot', 'latestError'])
            ->orderBy('name')
            ->get();

        // ── KPI globali — iterazione sulla collection già caricata, zero query aggiuntive ──
        $activeCount      = 0;
        $sitesWithFailures = 0;

        // All-time aggregati (da payload.periods.all_time, disponibili solo con V2)
        $allTimeOrders       = 0;
        $allTimeRevenue      = 0.0;
        $allTimeReservations = 0;
        $allTimeCovers       = 0;

        // Medie mensili globali = somma delle medie mensili per sito
        $monthlyAvgOrders       = 0.0;
        $monthlyAvgRevenue      = 0.0;
        $monthlyAvgReservations = 0.0;
        $monthlyAvgCovers       = 0.0;

        // Per il grafico e i dati "oggi / 7gg"
        $ordersTodayTotal        = 0;
        $reservationsTodayTotal  = 0;
        $ordersLast7Total        = 0;
        $reservationsLast7Total  = 0;

        foreach ($sites as $site) {
            if ($site->active) {
                $activeCount++;
            }
            if ($site->consecutive_failures > 0) {
                $sitesWithFailures++;
            }

            $snap = $site->latestSnapshot;
            if (! $snap) {
                continue;
            }

            // Legge all_time dal payload V2 se disponibile
            $allTimeBlock = is_array($snap->payload)
                ? ($snap->payload['periods']['all_time'] ?? null)
                : null;

            if ($allTimeBlock) {
                $siteOrders = (int)   ($allTimeBlock['orders_total']        ?? 0);
                $siteRev    = (float) ($allTimeBlock['orders_revenue']       ?? 0);
                $siteRes    = (int)   ($allTimeBlock['reservations_total']   ?? 0);
                $siteCov    = (int)   ($allTimeBlock['reservations_covers']  ?? 0);

                $allTimeOrders       += $siteOrders;
                $allTimeRevenue      += $siteRev;
                $allTimeReservations += $siteRes;
                $allTimeCovers       += $siteCov;

                // Mesi attivi: da period_from a period_to, cap a 5 anni per evitare
                // divisioni per periodi enormi causati dal from=2000-01-01 iniziale.
                $months = 1;
                if ($snap->period_from && $snap->period_to) {
                    $cap  = $snap->period_to->copy()->subYears(5);
                    $from = $snap->period_from->lt($cap) ? $cap : $snap->period_from;
                    $months = max(1, (int) ceil($from->diffInMonths($snap->period_to)));
                }

                $monthlyAvgOrders       += $months > 0 ? round($siteOrders / $months, 1) : 0;
                $monthlyAvgRevenue      += $months > 0 ? round($siteRev    / $months, 2) : 0;
                $monthlyAvgReservations += $months > 0 ? round($siteRes    / $months, 1) : 0;
                $monthlyAvgCovers       += $months > 0 ? round($siteCov    / $months, 1) : 0;
            }

            $ordersTodayTotal       += (int) ($snap->orders_today             ?? 0);
            $reservationsTodayTotal += (int) ($snap->reservations_today       ?? 0);
            $ordersLast7Total       += (int) ($snap->orders_last_7_days       ?? 0);
            $reservationsLast7Total += (int) ($snap->reservations_last_7_days ?? 0);
        }

        $kpis = [
            'active_count'        => $activeCount,
            'sites_with_failures' => $sitesWithFailures,
            // All-time
            'orders_all_time'       => $allTimeOrders,
            'revenue_all_time'      => $allTimeRevenue,
            'reservations_all_time' => $allTimeReservations,
            'covers_all_time'       => $allTimeCovers,
            // Medie mensili (somma delle medie per sito)
            'orders_monthly_avg'       => round($monthlyAvgOrders),
            'revenue_monthly_avg'      => round($monthlyAvgRevenue, 2),
            'reservations_monthly_avg' => round($monthlyAvgReservations),
            'covers_monthly_avg'       => round($monthlyAvgCovers),
            // Oggi / 7gg per grafico
            'orders_today'          => $ordersTodayTotal,
            'reservations_today'    => $reservationsTodayTotal,
            'orders_last_7'         => $ordersLast7Total,
            'reservations_last_7'   => $reservationsLast7Total,
            // Flag: nessun dato ancora (snapshot V2 non ancora arrivato)
            'has_v2_data' => $allTimeOrders > 0 || $allTimeReservations > 0,
            // Flag servizi non utilizzati (globalmente)
            'uses_orders'        => $allTimeOrders > 0,
            'uses_reservations'  => $allTimeReservations > 0,
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
                    'site'          => $site,
                    'reason'        => 'no_snapshot',
                    'last_activity' => null,
                ];
                continue;
            }

            // Snapshot V1: no usage block.
            $usageMenu = $snap->payload['usage']['menu'] ?? null;

            if ($usageMenu === null) {
                if (($snap->api_version ?? '1') !== '2') {
                    $inactiveSites[] = [
                        'site'          => $site,
                        'reason'        => 'no_v2',
                        'last_activity' => null,
                    ];
                }
                continue;
            }

            // Find most recent menu date.
            $rawDates = array_filter([
                $usageMenu['last_product_updated_at']    ?? null,
                $usageMenu['last_category_updated_at']   ?? null,
                $usageMenu['last_ingredient_updated_at'] ?? null,
            ]);

            if (empty($rawDates)) {
                $inactiveSites[] = [
                    'site'          => $site,
                    'reason'        => 'no_menu_activity',
                    'last_activity' => null,
                ];
                continue;
            }

            $lastActivityRaw = max($rawDates);

            try {
                if (\Carbon\Carbon::parse($lastActivityRaw)->lt($thirtyDaysAgo)) {
                    $inactiveSites[] = [
                        'site'          => $site,
                        'reason'        => 'stale_menu',
                        'last_activity' => $lastActivityRaw,
                    ];
                }
            } catch (\Throwable $e) {
                // Unparsable date — skip silently.
            }
        }

        // Sort: no_snapshot first, then no_v2, then no_menu_activity, then stale.
        $reasonOrder = ['no_snapshot' => 0, 'no_v2' => 1, 'no_menu_activity' => 2, 'stale_menu' => 3];
        usort($inactiveSites, fn($a, $b) => ($reasonOrder[$a['reason']] ?? 9) <=> ($reasonOrder[$b['reason']] ?? 9));
        $inactiveSites = array_slice($inactiveSites, 0, 5);

        return view('sites.index', compact('sites', 'kpis', 'inactiveSites'));
    }

    public function create()
    {
        return view('sites.create', ['site' => new Site()]);
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

        Site::create($data);

        return redirect()->route('dashboard')->with('success', 'Site created.');
    }

    public function show(Site $site)
    {
        $site->load([
            'latestSnapshot',
            'reportSnapshots' => fn ($query) => $query->latest('fetched_at')->limit(10),
            'syncErrors' => fn ($query) => $query->latest('occurred_at')->limit(10),
        ]);

        return view('sites.show', compact('site'));
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

    private function validationMessages(): array
    {
        return [
            'url.starts_with' => 'The dashboard URL must start with https://.',
        ];
    }
}
