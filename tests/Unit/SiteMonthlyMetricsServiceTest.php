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
        $this->assertSame(180.0, $metrics['estimated_order_savings']);
        $this->assertSame(168.0, $metrics['estimated_reservation_savings']);
        $this->assertSame(348.0, $metrics['estimated_total_savings']);
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
        $this->assertSame(348.0, $metrics['estimated_total_savings']);
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
        $this->assertSame(0.0, $metrics['estimated_total_savings']);
    }

    public function test_it_builds_monthly_trend_from_monthly_payload(): void
    {
        $trend = $this->service()->monthlyTrendForSnapshot($this->snapshot([
            'monthly' => [
                [
                    'month' => '2026-05',
                    'orders_total' => 10,
                    'orders_revenue' => 100,
                    'reservations_total' => 5,
                    'reservations_covers' => 20,
                ],
                [
                    'month' => '2026-06',
                    'orders_total' => 12,
                    'orders_revenue' => 80,
                    'reservations_total' => 4,
                    'reservations_covers' => 22,
                ],
            ],
        ]));

        $this->assertSame('monthly', $trend['source']);
        $this->assertCount(2, $trend['rows']);
        $this->assertSame('2026-06', $trend['rows'][1]['month']);
        $this->assertSame(20.0, $trend['rows'][1]['changes']['orders']['percent']);
        $this->assertSame('up', $trend['rows'][1]['changes']['orders']['state']);
        $this->assertSame(-20.0, $trend['rows'][1]['changes']['revenue']['percent']);
        $this->assertSame('down', $trend['rows'][1]['changes']['revenue']['state']);
        $this->assertSame(-20.0, $trend['rows'][1]['changes']['reservations']['percent']);
        $this->assertSame(100.0, $trend['rows'][0]['savings']);
        $this->assertSame(104.0, $trend['rows'][1]['savings']);
        $this->assertSame(4.0, $trend['rows'][1]['changes']['savings']['percent']);
        $this->assertSame('up', $trend['rows'][1]['changes']['savings']['state']);
    }

    public function test_it_falls_back_to_daily_payload_grouped_by_month(): void
    {
        $trend = $this->service()->monthlyTrendForSnapshot($this->snapshot([
            'daily' => [
                ['date' => '2026-05-30', 'orders' => 2, 'revenue' => 40, 'reservations' => 1, 'covers' => 4],
                ['date' => '2026-05-31', 'orders' => 3, 'revenue' => 60, 'reservations' => 2, 'covers' => 8],
                ['date' => '2026-06-01', 'orders' => 10, 'revenue' => 150, 'reservations' => 3, 'covers' => 9],
            ],
        ]));

        $this->assertSame('daily', $trend['source']);
        $this->assertCount(2, $trend['rows']);
        $this->assertSame(5, $trend['rows'][0]['orders']);
        $this->assertSame(100.0, $trend['rows'][0]['revenue']);
        $this->assertSame(68.0, $trend['rows'][0]['savings']);
        $this->assertSame(10, $trend['rows'][1]['orders']);
        $this->assertSame(100.0, $trend['rows'][1]['changes']['orders']['percent']);
    }

    public function test_it_marks_growth_from_zero_as_new_activity(): void
    {
        $trend = $this->service()->monthlyTrendForSnapshot($this->snapshot([
            'monthly' => [
                '2026-05' => ['orders_total' => 0, 'reservations_total' => 1],
                '2026-06' => ['orders_total' => 4, 'reservations_total' => 2],
            ],
        ]));

        $this->assertSame('new', $trend['rows'][1]['changes']['orders']['state']);
        $this->assertNull($trend['rows'][1]['changes']['orders']['percent']);
        $this->assertSame(100.0, $trend['rows'][1]['changes']['reservations']['percent']);
    }

    public function test_it_builds_monthly_trend_from_single_month_snapshots(): void
    {
        $trend = $this->service()->monthlyTrendForSnapshots([
            $this->snapshotFromAttributes([
                'period_from' => '2026-04-01',
                'period_to' => '2026-04-30',
                'orders_total' => 8,
                'orders_revenue' => 160,
                'reservations_total' => 4,
                'reservations_covers' => 12,
            ]),
            $this->snapshotFromAttributes([
                'period_from' => '2026-05-01',
                'period_to' => '2026-05-31',
                'orders_total' => 12,
                'orders_revenue' => 180,
                'reservations_total' => 6,
                'reservations_covers' => 18,
            ]),
            $this->snapshotFromAttributes([
                'period_from' => '2026-06-01',
                'period_to' => '2026-06-30',
                'orders_total' => 6,
                'orders_revenue' => 90,
                'reservations_total' => 3,
                'reservations_covers' => 9,
            ]),
        ]);

        $this->assertSame('snapshots', $trend['source']);
        $this->assertCount(3, $trend['rows']);
        $this->assertSame('2026-04', $trend['rows'][0]['month']);
        $this->assertSame('2026-06', $trend['rows'][2]['month']);
        $this->assertSame(80.0, $trend['rows'][0]['savings']);
        $this->assertSame(108.0, $trend['rows'][1]['savings']);
        $this->assertSame(54.0, $trend['rows'][2]['savings']);
        $this->assertSame(50.0, $trend['rows'][1]['changes']['orders']['percent']);
        $this->assertSame(-50.0, $trend['rows'][2]['changes']['orders']['percent']);
        $this->assertSame(-50.0, $trend['rows'][2]['changes']['savings']['percent']);
    }

    private function service(): SiteMonthlyMetricsService
    {
        return new SiteMonthlyMetricsService;
    }

    private function snapshot(array $payload): ReportSnapshot
    {
        return new ReportSnapshot(['payload' => $payload]);
    }

    private function snapshotFromAttributes(array $attributes): ReportSnapshot
    {
        $snapshot = new ReportSnapshot;
        $snapshot->setRawAttributes($attributes, true);

        return $snapshot;
    }
}
