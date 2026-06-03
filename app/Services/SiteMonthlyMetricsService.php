<?php

namespace App\Services;

use App\Models\ReportSnapshot;
use Illuminate\Support\Arr;

class SiteMonthlyMetricsService
{
    /**
     * @return array{
     *     has_all_time: bool,
     *     orders_total: int,
     *     orders_revenue: float|null,
     *     reservations_total: int,
     *     reservations_covers: int,
     *     orders_active_months: int|null,
     *     reservations_active_months: int|null,
     *     orders_monthly_avg: float|null,
     *     revenue_monthly_avg: float|null,
     *     reservations_monthly_avg: float|null,
     *     covers_monthly_avg: float|null
     * }
     */
    public function forSnapshot(?ReportSnapshot $snapshot): array
    {
        $payload = is_array($snapshot?->payload) ? $snapshot->payload : [];
        $allTime = Arr::get($payload, 'periods.all_time');

        if (! is_array($allTime)) {
            return $this->emptyMetrics(false);
        }

        $ordersTotal = $this->integerValue(Arr::get($allTime, 'orders_total'));
        $ordersRevenue = $this->nullableFloat(Arr::get($allTime, 'orders_revenue'));
        $reservationsTotal = $this->integerValue(Arr::get($allTime, 'reservations_total'));
        $reservationsCovers = $this->integerValue(Arr::get($allTime, 'reservations_covers'));

        $ordersActiveMonths = $this->positiveInteger(Arr::get($allTime, 'orders_active_months'));
        $reservationsActiveMonths = $this->positiveInteger(Arr::get($allTime, 'reservations_active_months'));

        return [
            'has_all_time' => true,
            'orders_total' => $ordersTotal,
            'orders_revenue' => $ordersRevenue,
            'reservations_total' => $reservationsTotal,
            'reservations_covers' => $reservationsCovers,
            'orders_active_months' => $ordersActiveMonths,
            'reservations_active_months' => $reservationsActiveMonths,
            'orders_monthly_avg' => $this->average($ordersTotal, $ordersActiveMonths, 0),
            'revenue_monthly_avg' => $ordersRevenue !== null
                ? $this->average($ordersRevenue, $ordersActiveMonths, 2)
                : null,
            'reservations_monthly_avg' => $this->average($reservationsTotal, $reservationsActiveMonths, 0),
            'covers_monthly_avg' => $this->average($reservationsCovers, $reservationsActiveMonths, 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMetrics(bool $hasAllTime): array
    {
        return [
            'has_all_time' => $hasAllTime,
            'orders_total' => 0,
            'orders_revenue' => null,
            'reservations_total' => 0,
            'reservations_covers' => 0,
            'orders_active_months' => null,
            'reservations_active_months' => null,
            'orders_monthly_avg' => null,
            'revenue_monthly_avg' => null,
            'reservations_monthly_avg' => null,
            'covers_monthly_avg' => null,
        ];
    }

    private function average(int|float $total, ?int $months, int $precision): ?float
    {
        if ($months === null || $months <= 0) {
            return null;
        }

        return round($total / $months, $precision);
    }

    private function integerValue(mixed $value): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return 0;
        }

        return (int) round((float) $value);
    }

    private function positiveInteger(mixed $value): ?int
    {
        $integer = $this->integerValue($value);

        return $integer > 0 ? $integer : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
