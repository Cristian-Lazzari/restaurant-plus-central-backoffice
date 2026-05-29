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

        // Accumulate KPIs iterating the already-loaded collection — no extra queries.
        $activeCount             = 0;
        $ordersTotal             = 0;
        $revenueTotal            = 0;
        $reservationsTotal       = 0;
        $coversTotal             = 0;
        $sitesWithFailures       = 0;
        $revenueUnavailable      = 0;
        $ordersTodayTotal        = 0;
        $reservationsTodayTotal  = 0;
        $ordersLast7Total        = 0;
        $ordersLast30Total       = 0;

        // Collect the snapshot dates for a period label.
        $periodDates = [];

        foreach ($sites as $site) {
            if ($site->active) {
                $activeCount++;
            }

            if ($site->consecutive_failures > 0) {
                $sitesWithFailures++;
            }

            $snap = $site->latestSnapshot;

            if ($snap) {
                $ordersTotal       += (int) ($snap->orders_total ?? 0);
                $reservationsTotal += (int) ($snap->reservations_total ?? 0);
                $coversTotal       += (int) ($snap->reservations_covers ?? 0);

                // Per-period counters — disponibili solo da snapshot V2, null altrimenti.
                $ordersTodayTotal       += (int) ($snap->orders_today ?? 0);
                $reservationsTodayTotal += (int) ($snap->reservations_today ?? 0);
                $ordersLast7Total       += (int) ($snap->orders_last_7_days ?? 0);
                $ordersLast30Total      += (int) ($snap->orders_last_30_days ?? 0);

                if ($snap->revenue_unit === 'euros' && $snap->orders_revenue !== null) {
                    $revenueTotal += (int) $snap->orders_revenue;
                } else {
                    $revenueUnavailable++;
                }

                if ($snap->period_from) {
                    $periodDates[] = $snap->period_from->toDateString();
                }
                if ($snap->period_to) {
                    $periodDates[] = $snap->period_to->toDateString();
                }
            }
        }

        // Build a human-readable period label from the range of snapshot dates.
        $periodLabel = 'Nessun dato';
        if (! empty($periodDates)) {
            $min = min($periodDates);
            $max = max($periodDates);
            $periodLabel = $min === $max ? $min : $min . ' – ' . $max;
        }

        $kpis = [
            'active_count'              => $activeCount,
            'orders_total'              => $ordersTotal,
            'revenue_total'             => $revenueTotal,
            'reservations_total'        => $reservationsTotal,
            'covers_total'              => $coversTotal,
            'sites_with_failures'       => $sitesWithFailures,
            'revenue_unavailable_count' => $revenueUnavailable,
            'period_label'              => $periodLabel,
            // Aggregati V2: nulli/zero se nessun sito ha ancora snapshot V2.
            'orders_today'              => $ordersTodayTotal,
            'reservations_today'        => $reservationsTodayTotal,
            'orders_last_7'             => $ordersLast7Total,
            'orders_last_30'            => $ordersLast30Total,
            // Flag per mostrare/nascondere il messaggio "in attesa snapshot V2".
            'has_today_data'            => $ordersTodayTotal > 0 || $reservationsTodayTotal > 0,
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
