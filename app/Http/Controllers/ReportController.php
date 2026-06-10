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
        $hasRevenue       = false;
        $hasCovers        = false;
        $allTimeMissing   = false; // true se 'all' fallback ai 30gg

        foreach ($sites as $site) {
            $snap = $site->latestSnapshot;

            // Per 'all' prova le colonne total; se nulle, usa le 30gg come fallback
            $ordersCol = $cols['orders'];
            $resCol    = $cols['res'];
            if ($period === 'all' && $snap && $snap->{$ordersCol} === null) {
                $ordersCol      = 'orders_last_30_days';
                $resCol         = 'reservations_last_30_days';
                $allTimeMissing = true;
            }

            $orders       = $snap !== null ? (int) ($snap->{$ordersCol} ?? 0) : 0;
            $reservations = $snap !== null ? (int) ($snap->{$resCol}    ?? 0) : 0;
            $coversRaw = ($cols['covers'] && $snap !== null) ? $snap->{$cols['covers']} : null;
            // Fallback dal payload JSON per snapshot precedenti al fix del sync
            if ($coversRaw === null && $snap !== null && is_array($snap->payload)) {
                $p         = $snap->payload;
                $coversRaw = $p['periods']['all_time']['total_covers']
                    ?? $p['reservations']['total_covers']
                    ?? null;
            }
            $covers = $coversRaw !== null ? (int) $coversRaw : null;
            $revenue      = $this->resolveRevenue($snap, $cols['revenue']);

            if ($revenue !== null) { $hasRevenue = true; }
            if ($covers  !== null && $covers > 0) { $hasCovers = true; }

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
                DB::raw(DB::getDriverName() === 'sqlite'
                    ? "strftime('%Y-%m', rs.fetched_at) as month"
                    : "DATE_FORMAT(rs.fetched_at, '%Y-%m') as month"),
                'rs.orders_current_month',
                'rs.orders_last_30_days',
                'rs.reservations_current_month',
                'rs.reservations_last_30_days',
                'rs.orders_revenue',
                'rs.revenue_unit',
                'rs.reservations_covers',
            ])
            ->get();

        [$monthlyData, $siteNames] = $this->buildMonthlyData($rawSnaps);

        return view('reports.index', compact(
            'ordersRows', 'reservationRows', 'totals',
            'period', 'monthlyData', 'siteNames',
            'hasRevenue', 'hasCovers', 'allTimeMissing'
        ));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveRevenue($snap, ?string $col): ?float
    {
        if ($snap === null) {
            return null;
        }

        // Usa la colonna pre-calcolata se disponibile
        $raw = ($col !== null) ? $snap->{$col} : null;

        // Fallback: leggi dal payload JSON (per snapshot precedenti al fix del sync)
        if ($raw === null && is_array($snap->payload)) {
            $p   = $snap->payload;
            $raw = $p['periods']['all_time']['revenue_confirmed']
                ?? $p['orders']['revenue_confirmed']
                ?? null;
        }

        if ($raw === null) {
            return null;
        }

        $unit = strtolower(trim((string) ($snap->revenue_unit ?? '')));
        return round($unit === 'cents' ? (float) $raw / 100 : (float) $raw, 2);
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
                'orders'       => (int) ($s->orders_current_month       ?? $s->orders_last_30_days       ?? 0),
                'reservations' => (int) ($s->reservations_current_month ?? $s->reservations_last_30_days ?? 0),
                'covers'       => (int) ($s->reservations_covers        ?? 0),
                'revenue'      => $revenue,
            ];
        }

        krsort($monthlyData); // mesi più recenti prima

        return [$monthlyData, $siteNames];
    }
}
