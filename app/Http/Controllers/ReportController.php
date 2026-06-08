<?php

namespace App\Http\Controllers;

use App\Models\Site;

class ReportController extends Controller
{
    public function index()
    {
        $sites = Site::with('latestSnapshot')->where('active', true)->orderBy('name')->get();

        $rows = [];
        $totalOrders30 = 0;
        $totalRevenue30 = 0.0;
        $totalReservations30 = 0;

        foreach ($sites as $site) {
            $snap = $site->latestSnapshot;

            $orders30 = (int) ($snap->orders_last_30_days ?? 0);
            $reservations30 = (int) ($snap->reservations_last_30_days ?? 0);

            $revenueRaw = $snap->orders_revenue ?? null;
            if ($revenueRaw !== null && ($snap->revenue_unit ?? '') === 'cents') {
                $revenue30 = round((float) $revenueRaw / 100, 2);
            } elseif ($revenueRaw !== null) {
                $revenue30 = round((float) $revenueRaw, 2);
            } else {
                $revenue30 = null;
            }

            $totalOrders30 += $orders30;
            $totalRevenue30 += $revenue30 ?? 0.0;
            $totalReservations30 += $reservations30;

            $rows[] = [
                'site_id'       => $site->id,
                'site_name'     => $site->name,
                'orders30'      => $orders30,
                'revenue30'     => $revenue30,
                'reservations30' => $reservations30,
                'last_sync'     => $snap?->fetched_at,
            ];
        }

        usort($rows, fn ($a, $b) => $b['orders30'] <=> $a['orders30']);

        $totals = [
            'orders30'       => $totalOrders30,
            'revenue30'      => round($totalRevenue30, 2),
            'reservations30' => $totalReservations30,
        ];

        return view('reports.index', compact('rows', 'totals'));
    }
}
