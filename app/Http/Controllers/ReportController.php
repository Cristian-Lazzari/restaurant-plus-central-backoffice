<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Mappa periodo → colonne del snapshot
    private const PERIOD_MAP = [
        '1'   => ['orders' => 'orders_today',        'res' => 'reservations_today',        'revenue' => null,             'covers' => null],
        '7'   => ['orders' => 'orders_last_7_days',   'res' => 'reservations_last_7_days',   'revenue' => null,             'covers' => null],
        '30'  => ['orders' => 'orders_last_30_days',  'res' => 'reservations_last_30_days',  'revenue' => 'orders_revenue', 'covers' => 'reservations_covers'],
        'all' => ['orders' => 'orders_total',         'res' => 'reservations_total',         'revenue' => 'orders_revenue', 'covers' => 'reservations_covers'],
    ];

    public function index(Request $request)
    {
        $period = $request->input('period', '30');
        if (! array_key_exists($period, self::PERIOD_MAP)) {
            $period = '30';
        }
        $cols = self::PERIOD_MAP[$period];

        // ── Dati per sito (snapshot più recente) ─────────────────────────────
        $sites = Site::connected()
            ->with('latestSnapshot')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $ordersRows       = [];
        $reservationRows  = [];
        $totOrders        = 0;
        $totRevenue       = 0.0;
        $totReservations  = 0;
        $totCovers        = 0;

        foreach ($sites as $site) {
            $snap = $site->latestSnapshot;

            $orders       = (int) ($snap?->{$cols['orders']} ?? 0);
            $reservations = (int) ($snap?->{$cols['res']}    ?? 0);
            $covers       = $cols['covers']  ? (int) ($snap?->{$cols['covers']}  ?? 0) : null;
            $revenue      = $this->resolveRevenue($snap, $cols['revenue']);

            $totOrders       += $orders;
            $totRevenue      += $revenue ?? 0.0;
            $totReservations += $reservations;
            $totCovers       += $covers ?? 0;

            $base = ['site_id' => $site->id, 'site_name' => $site->name, 'last_sync' => $snap?->fetched_at];

            $ordersRows[]      = $base + ['orders' => $orders, 'revenue' => $revenue];
            $reservationRows[] = $base + ['reservations' => $reservations, 'covers' => $covers];
        }

        usort($ordersRows,     fn ($a, $b) => $b['orders']       <=> $a['orders']);
        usort($reservationRows, fn ($a, $b) => $b['reservations'] <=> $a['reservations']);

        $totals = [
            'orders'       => $totOrders,
            'revenue'      => round($totRevenue, 2),
            'reservations' => $totReservations,
            'covers'       => $totCovers,
        ];

        // ── Riepilogo mensile ─────────────────────────────────────────────────
        // Per ogni (sito × mese) prendo lo snapshot più recente del mese,
        // e uso orders_last_30_days / reservations_last_30_days come proxy mensile.
        $rawSnaps = DB::table('report_snapshots as rs')
            ->join('sites', 'rs.site_id', '=', 'sites.id')
            ->where('sites.is_prospect', false)
            ->where('sites.active', true)
            ->orderBy('rs.fetched_at', 'desc')
            ->select([
                'rs.site_id',
                'sites.name as site_name',
                DB::raw("DATE_FORMAT(rs.fetched_at, '%Y-%m') as month"),
                'rs.orders_last_30_days',
                'rs.reservations_last_30_days',
                'rs.orders_revenue',
                'rs.revenue_unit',
                'rs.reservations_covers',
            ])
            ->get();

        [$monthlyData, $siteNames] = $this->buildMonthlyData($rawSnaps);

        return view('reports.index', compact(
            'ordersRows', 'reservationRows', 'totals',
            'period', 'monthlyData', 'siteNames'
        ));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveRevenue($snap, ?string $col): ?float
    {
        if ($col === null || $snap === null || $snap->{$col} === null) {
            return null;
        }
        $raw = (float) $snap->{$col};
        return round(($snap->revenue_unit === 'cents') ? $raw / 100 : $raw, 2);
    }

    private function buildMonthlyData($rawSnaps): array
    {
        $seen        = [];
        $monthlyData = [];
        $siteNames   = [];

        foreach ($rawSnaps as $s) {
            $key = $s->site_id . '_' . $s->month;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $siteNames[$s->site_id] = $s->site_name;

            $revenue = null;
            if ($s->orders_revenue !== null) {
                $raw     = (float) $s->orders_revenue;
                $revenue = round($s->revenue_unit === 'cents' ? $raw / 100 : $raw, 2);
            }

            $monthlyData[$s->month][$s->site_id] = [
                'orders'       => (int) ($s->orders_last_30_days    ?? 0),
                'reservations' => (int) ($s->reservations_last_30_days ?? 0),
                'covers'       => (int) ($s->reservations_covers     ?? 0),
                'revenue'      => $revenue,
            ];
        }

        krsort($monthlyData); // mesi più recenti prima

        return [$monthlyData, $siteNames];
    }
}
