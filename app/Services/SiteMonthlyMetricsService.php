<?php

namespace App\Services;

use App\Models\ReportSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class SiteMonthlyMetricsService
{
    public const ORDER_MARKETPLACE_COMMISSION_RATE = 0.20;

    public const RESERVATION_MARKETPLACE_COVER_FEE = 4.0;

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
     *     covers_monthly_avg: float|null,
     *     estimated_order_savings: float,
     *     estimated_reservation_savings: float,
     *     estimated_total_savings: float
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
        $savings = $this->estimatedSavings($ordersRevenue, $reservationsCovers);

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
            'estimated_order_savings' => $savings['order_savings'],
            'estimated_reservation_savings' => $savings['reservation_savings'],
            'estimated_total_savings' => $savings['total_savings'],
        ];
    }

    /**
     * @return array{order_savings: float, reservation_savings: float, total_savings: float}
     */
    public function estimatedSavings(?float $ordersRevenue, int $reservationCovers): array
    {
        $orderSavings = $ordersRevenue !== null
            ? round(max(0.0, $ordersRevenue) * self::ORDER_MARKETPLACE_COMMISSION_RATE, 2)
            : 0.0;
        $reservationSavings = round(max(0, $reservationCovers) * self::RESERVATION_MARKETPLACE_COVER_FEE, 2);

        return [
            'order_savings' => $orderSavings,
            'reservation_savings' => $reservationSavings,
            'total_savings' => round($orderSavings + $reservationSavings, 2),
        ];
    }

    /**
     * @return array{
     *     source: string|null,
     *     rows: list<array{
     *         month: string,
     *         label: string,
     *         orders: int,
     *         revenue: float|null,
     *         reservations: int,
     *         covers: int,
     *         order_savings: float,
     *         reservation_savings: float,
     *         savings: float,
     *         changes: array<string, array{state: string, percent: float|null}>
     *     }>
     * }
     */
    public function monthlyTrendForSnapshot(?ReportSnapshot $snapshot): array
    {
        $payload = is_array($snapshot?->payload) ? $snapshot->payload : [];

        $rows = $this->monthlyRowsFromPayload($payload);
        $source = count($rows) > 0 ? 'monthly' : null;

        if (count($rows) === 0) {
            $rows = $this->monthlyRowsFromDaily($payload);
            $source = count($rows) > 0 ? 'daily' : null;
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->rowHasActivity($row)));

        usort($rows, fn (array $a, array $b): int => $a['month'] <=> $b['month']);

        return [
            'source' => $source,
            'rows' => $this->attachMonthlyChanges($rows),
        ];
    }

    /**
     * @param iterable<ReportSnapshot> $snapshots
     * @return array{
     *     source: string|null,
     *     rows: list<array{
     *         month: string,
     *         label: string,
     *         orders: int,
     *         revenue: float|null,
     *         reservations: int,
     *         covers: int,
     *         order_savings: float,
     *         reservation_savings: float,
     *         savings: float,
     *         changes: array<string, array{state: string, percent: float|null}>
     *     }>
     * }
     */
    public function monthlyTrendForSnapshots(iterable $snapshots, ?ReportSnapshot $fallbackSnapshot = null): array
    {
        $rows = [];
        $source = null;

        foreach ($snapshots as $snapshot) {
            if (! $snapshot instanceof ReportSnapshot) {
                continue;
            }

            $snapshotRows = $this->monthlyRowsForSnapshot($snapshot);

            if (count($snapshotRows) === 0) {
                continue;
            }

            foreach ($snapshotRows as $row) {
                $this->replaceMonthlyRow($rows, $row);
            }

            $source = 'snapshots';
        }

        if (count($rows) === 0 && $fallbackSnapshot) {
            return $this->monthlyTrendForSnapshot($fallbackSnapshot);
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->rowHasActivity($row)));

        usort($rows, fn (array $a, array $b): int => $a['month'] <=> $b['month']);

        return [
            'source' => $source,
            'rows' => $this->attachMonthlyChanges($rows),
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
            'estimated_order_savings' => 0.0,
            'estimated_reservation_savings' => 0.0,
            'estimated_total_savings' => 0.0,
        ];
    }

    /**
     * @return array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}>
     */
    private function monthlyRowsForSnapshot(ReportSnapshot $snapshot): array
    {
        $payload = is_array($snapshot->payload) ? $snapshot->payload : [];

        $rows = $this->monthlyRowsFromPayload($payload);

        if (count($rows) > 0) {
            return $rows;
        }

        $month = $this->singleMonthForSnapshot($snapshot);

        if ($month !== null) {
            $row = $this->baseMonthlyRow($month);
            $row['orders'] = (int) ($snapshot->orders_total ?? $this->integerValue(Arr::get($payload, 'orders.total')));
            $row['revenue'] = $snapshot->orders_revenue !== null
                ? (float) $snapshot->orders_revenue
                : $this->nullableFloat(Arr::get($payload, 'orders.revenue_confirmed') ?? Arr::get($payload, 'orders.revenue'));
            $row['reservations'] = (int) ($snapshot->reservations_total ?? $this->integerValue(Arr::get($payload, 'reservations.total')));
            $row['covers'] = (int) ($snapshot->reservations_covers ?? $this->integerValue(Arr::get($payload, 'reservations.total_covers')));

            return [$month => $this->withSavings($row)];
        }

        return $this->monthlyRowsFromDaily($payload);
    }

    /**
     * @return array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}>
     */
    private function monthlyRowsFromPayload(array $payload): array
    {
        foreach (['monthly', 'months', 'periods.monthly', 'periods.months', 'trends.monthly'] as $path) {
            $candidate = Arr::get($payload, $path);

            if (! is_array($candidate) || count($candidate) === 0) {
                continue;
            }

            $rows = $this->normalizeMonthlyCandidate($candidate);

            if (count($rows) > 0) {
                return $rows;
            }
        }

        return [];
    }

    /**
     * @return array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}>
     */
    private function monthlyRowsFromDaily(array $payload): array
    {
        $daily = Arr::get($payload, 'daily', []);

        if (! is_array($daily)) {
            return [];
        }

        $rows = [];

        foreach ($daily as $day) {
            if (! is_array($day)) {
                continue;
            }

            $month = $this->monthKey($day['date'] ?? null);

            if ($month === null) {
                continue;
            }

            $row = $this->baseMonthlyRow($month);
            $row['orders'] = $this->integerValue($this->firstNumeric($day, ['orders', 'orders_total']));
            $row['revenue'] = $this->nullableFloat($this->firstNumeric($day, ['revenue', 'orders_revenue', 'revenue_confirmed']));
            $row['reservations'] = $this->integerValue($this->firstNumeric($day, ['reservations', 'reservations_total']));
            $row['covers'] = $this->integerValue($this->firstNumeric($day, ['covers', 'reservations_covers', 'total_covers']));

            $this->mergeMonthlyRow($rows, $row);
        }

        return $this->withSavingsForRows($rows);
    }

    /**
     * @return array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}>
     */
    private function normalizeMonthlyCandidate(array $candidate): array
    {
        $rows = [];

        foreach ($candidate as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $month = $this->monthKey($value['month'] ?? $value['period'] ?? $value['date'] ?? $value['from'] ?? (is_string($key) ? $key : null));

            if ($month === null) {
                continue;
            }

            $row = $this->baseMonthlyRow($month);
            $row['orders'] = $this->integerValue($this->firstNumeric($value, ['orders', 'orders_total', 'orders_count']));
            $row['revenue'] = $this->nullableFloat($this->firstNumeric($value, ['revenue', 'orders_revenue', 'revenue_confirmed']));
            $row['reservations'] = $this->integerValue($this->firstNumeric($value, ['reservations', 'reservations_total', 'reservations_count']));
            $row['covers'] = $this->integerValue($this->firstNumeric($value, ['covers', 'reservations_covers', 'total_covers']));

            $this->mergeMonthlyRow($rows, $row);
        }

        return $this->withSavingsForRows($rows);
    }

    /**
     * @return array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int, order_savings: float, reservation_savings: float, savings: float}
     */
    private function baseMonthlyRow(string $month): array
    {
        return [
            'month' => $month,
            'label' => CarbonImmutable::createFromFormat('Y-m', $month)->locale('it')->translatedFormat('F Y'),
            'orders' => 0,
            'revenue' => null,
            'reservations' => 0,
            'covers' => 0,
            'order_savings' => 0.0,
            'reservation_savings' => 0.0,
            'savings' => 0.0,
        ];
    }

    /**
     * @param array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}> $rows
     * @param array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int} $row
     */
    private function mergeMonthlyRow(array &$rows, array $row): void
    {
        $month = $row['month'];

        if (! isset($rows[$month])) {
            $rows[$month] = $row;

            return;
        }

        $rows[$month]['orders'] += $row['orders'];
        $rows[$month]['reservations'] += $row['reservations'];
        $rows[$month]['covers'] += $row['covers'];

        if ($row['revenue'] !== null) {
            $rows[$month]['revenue'] = ($rows[$month]['revenue'] ?? 0) + $row['revenue'];
        }
    }

    /**
     * @param array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int} $row
     * @return array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int, order_savings: float, reservation_savings: float, savings: float}
     */
    private function withSavings(array $row): array
    {
        $savings = $this->estimatedSavings($row['revenue'], (int) $row['covers']);

        $row['order_savings'] = $savings['order_savings'];
        $row['reservation_savings'] = $savings['reservation_savings'];
        $row['savings'] = $savings['total_savings'];

        return $row;
    }

    /**
     * @param array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}> $rows
     * @return array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int, order_savings: float, reservation_savings: float, savings: float}>
     */
    private function withSavingsForRows(array $rows): array
    {
        foreach ($rows as $month => $row) {
            $rows[$month] = $this->withSavings($row);
        }

        return $rows;
    }

    /**
     * @param array<string, array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}> $rows
     * @param array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int} $row
     */
    private function replaceMonthlyRow(array &$rows, array $row): void
    {
        $rows[$row['month']] = $row;
    }

    private function singleMonthForSnapshot(ReportSnapshot $snapshot): ?string
    {
        $rawFrom = $snapshot->getRawOriginal('period_from');
        $rawTo = $snapshot->getRawOriginal('period_to');

        if (! $rawFrom || ! $rawTo) {
            return null;
        }

        $from = CarbonImmutable::parse($rawFrom);
        $to = CarbonImmutable::parse($rawTo);

        if ($from->format('Y-m') !== $to->format('Y-m')) {
            return null;
        }

        return $from->format('Y-m');
    }

    private function rowHasActivity(array $row): bool
    {
        return (int) ($row['orders'] ?? 0) > 0
            || (float) ($row['revenue'] ?? 0) > 0
            || (int) ($row['reservations'] ?? 0) > 0
            || (int) ($row['covers'] ?? 0) > 0;
    }

    /**
     * @param list<array{month: string, label: string, orders: int, revenue: float|null, reservations: int, covers: int}> $rows
     * @return list<array{
     *     month: string,
     *     label: string,
     *     orders: int,
     *     revenue: float|null,
     *     reservations: int,
     *     covers: int,
     *     order_savings: float,
     *     reservation_savings: float,
     *     savings: float,
     *     changes: array<string, array{state: string, percent: float|null}>
     * }>
     */
    private function attachMonthlyChanges(array $rows): array
    {
        $previous = null;

        foreach ($rows as $index => $row) {
            $rows[$index]['changes'] = [
                'orders' => $this->changeDescriptor($row['orders'], $previous['orders'] ?? null),
                'revenue' => $this->changeDescriptor($row['revenue'], $previous['revenue'] ?? null),
                'reservations' => $this->changeDescriptor($row['reservations'], $previous['reservations'] ?? null),
                'covers' => $this->changeDescriptor($row['covers'], $previous['covers'] ?? null),
                'savings' => $this->changeDescriptor($row['savings'], $previous['savings'] ?? null),
            ];

            $previous = $row;
        }

        return $rows;
    }

    /**
     * @return array{state: string, percent: float|null}
     */
    private function changeDescriptor(int|float|null $current, int|float|null $previous): array
    {
        if ($previous === null || $current === null) {
            return ['state' => 'none', 'percent' => null];
        }

        if ((float) $previous === 0.0) {
            if ((float) $current === 0.0) {
                return ['state' => 'flat', 'percent' => 0.0];
            }

            return ['state' => 'new', 'percent' => null];
        }

        $percent = round(((float) $current - (float) $previous) / abs((float) $previous) * 100, 1);

        return [
            'state' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
            'percent' => $percent,
        ];
    }

    private function monthKey(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return $value;
        }

        try {
            return CarbonImmutable::parse($value)->format('Y-m');
        } catch (\Throwable) {
            return null;
        }
    }

    private function firstNumeric(array $source, array $keys): int|float|null
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if ($value !== null && $value !== '' && is_numeric($value)) {
                return str_contains((string) $value, '.') ? (float) $value : (int) $value;
            }
        }

        return null;
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
