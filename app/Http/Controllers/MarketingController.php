<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Support\Facades\DB;

class MarketingController extends Controller
{
    public function index()
    {
        $activeCount = Site::where('active', true)->count();

        $sites = Site::with('latestSnapshot')->where('active', true)->get();

        $orders30Values = [];
        $top5 = [];

        foreach ($sites as $site) {
            $snap = $site->latestSnapshot;
            $orders30 = (int) ($snap->orders_last_30_days ?? 0);
            $orders30Values[] = $orders30;
            $top5[] = [
                'site_id'   => $site->id,
                'site_name' => $site->name,
                'orders30'  => $orders30,
            ];
        }

        $avgOrders30 = count($orders30Values) > 0
            ? round(array_sum($orders30Values) / count($orders30Values), 1)
            : 0;

        usort($top5, fn ($a, $b) => $b['orders30'] <=> $a['orders30']);
        $top5 = array_slice($top5, 0, 5);

        $packDistribution = Site::select('pack', DB::raw('count(*) as total'))
            ->groupBy('pack')
            ->orderBy('pack')
            ->get();

        return view('marketing.index', compact('activeCount', 'avgOrders30', 'top5', 'packDistribution'));
    }
}
