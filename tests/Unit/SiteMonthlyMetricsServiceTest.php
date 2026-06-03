<?php

namespace Tests\Unit;

use App\Models\ReportSnapshot;
use App\Services\SiteMonthlyMetricsService;
use PHPUnit\Framework\TestCase;

class SiteMonthlyMetricsServiceTest extends TestCase
{
    public function test_it_uses_real_active_months_for_monthly_averages(): void
    {
        $metrics = $this->service()->forSnapshot($this->snapshot([
            'periods' => [
                'all_time' => [
                    'orders_total' => 60,
                    'orders_revenue' => 900.0,
                    'orders_active_months' => 3,
                    'reservations_total' => 14,
                    'reservations_covers' => 42,
                    'reservations_active_months' => 2,
                ],
            ],
        ]));

        $this->assertSame(20.0, $metrics['orders_monthly_avg']);
        $this->assertSame(300.0, $metrics['revenue_monthly_avg']);
        $this->assertSame(7.0, $metrics['reservations_monthly_avg']);
        $this->assertSame(21.0, $metrics['covers_monthly_avg']);
    }

    public function test_it_does_not_fall_back_to_calendar_months_when_active_months_are_missing(): void
    {
        $metrics = $this->service()->forSnapshot($this->snapshot([
            'periods' => [
                'all_time' => [
                    'from' => '2000-01-01',
                    'to' => '2026-06-03',
                    'orders_total' => 60,
                    'orders_revenue' => 900.0,
                    'reservations_total' => 14,
                    'reservations_covers' => 42,
                ],
            ],
        ]));

        $this->assertSame(60, $metrics['orders_total']);
        $this->assertNull($metrics['orders_monthly_avg']);
        $this->assertNull($metrics['revenue_monthly_avg']);
        $this->assertNull($metrics['reservations_monthly_avg']);
        $this->assertNull($metrics['covers_monthly_avg']);
    }

    public function test_it_keeps_zero_revenue_average_when_active_months_are_known(): void
    {
        $metrics = $this->service()->forSnapshot($this->snapshot([
            'periods' => [
                'all_time' => [
                    'orders_total' => 4,
                    'orders_revenue' => 0,
                    'orders_active_months' => 2,
                ],
            ],
        ]));

        $this->assertSame(2.0, $metrics['orders_monthly_avg']);
        $this->assertSame(0.0, $metrics['revenue_monthly_avg']);
    }

    private function service(): SiteMonthlyMetricsService
    {
        return new SiteMonthlyMetricsService;
    }

    private function snapshot(array $payload): ReportSnapshot
    {
        return new ReportSnapshot(['payload' => $payload]);
    }
}
