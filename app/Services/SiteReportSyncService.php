<?php

namespace App\Services;

use App\Models\ReportSnapshot;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiteReportSyncService
{
    public function sync(Site $site, ?string $from = null, ?string $to = null): array
    {
        $endpoint = $this->endpointFor($site);
        $startedAt = microtime(true);

        $this->logInfo('sync started', [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'endpoint' => $endpoint,
            'from' => $from,
            'to' => $to,
        ]);

        if (! $this->isHttpsUrl($site->url)) {
            $responseTimeMs = $this->responseTimeMs($startedAt);

            return $this->fail($site, 'EXCEPTION', 'Dashboard URL must start with https:// before sync.', [
                'endpoint' => $endpoint,
                'reason' => 'insecure_url',
                'response_time_ms' => $responseTimeMs,
            ], $responseTimeMs);
        }

        try {
            $token = $site->token;

            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($endpoint, array_filter([
                    'from' => $from,
                    'to' => $to,
                ], fn ($value) => $value !== null && $value !== ''));
        } catch (DecryptException $e) {
            $responseTimeMs = $this->responseTimeMs($startedAt);

            return $this->fail($site, 'TOKEN_DECRYPT_FAILED', 'Saved site token cannot be decrypted. Check that APP_KEY did not change.', [
                'endpoint' => $endpoint,
                'from' => $from,
                'to' => $to,
                'response_time_ms' => $responseTimeMs,
                'exception' => class_basename($e),
                'exception_message' => $this->excerpt($e->getMessage()),
            ], $responseTimeMs);
        } catch (ConnectionException $e) {
            $responseTimeMs = $this->responseTimeMs($startedAt);

            return $this->fail($site, 'TIMEOUT', 'Report request timed out or connection failed', [
                'endpoint' => $endpoint,
                'from' => $from,
                'to' => $to,
                'response_time_ms' => $responseTimeMs,
                'exception' => class_basename($e),
                'exception_message' => $this->excerpt($e->getMessage()),
            ], $responseTimeMs);
        } catch (Throwable $e) {
            $responseTimeMs = $this->responseTimeMs($startedAt);

            return $this->fail($site, 'EXCEPTION', 'Report request failed', [
                'endpoint' => $endpoint,
                'from' => $from,
                'to' => $to,
                'response_time_ms' => $responseTimeMs,
                'exception' => class_basename($e),
                'exception_message' => $this->excerpt($e->getMessage()),
            ], $responseTimeMs);
        }

        $responseTimeMs = $this->responseTimeMs($startedAt);
        $httpStatusCode = $response->status();

        if (! $response->successful()) {
            return $this->fail($site, 'HTTP_ERROR', 'Report endpoint returned HTTP ' . $httpStatusCode, [
                'endpoint' => $endpoint,
                'from' => $from,
                'to' => $to,
                'status' => $httpStatusCode,
                'response_time_ms' => $responseTimeMs,
                'body_excerpt' => $this->excerpt($response->body()),
            ], $responseTimeMs, $httpStatusCode);
        }

        $payload = json_decode($response->body(), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($payload)) {
            return $this->fail($site, 'INVALID_JSON', 'Report endpoint returned invalid JSON', [
                'endpoint' => $endpoint,
                'from' => $from,
                'to' => $to,
                'status' => $httpStatusCode,
                'response_time_ms' => $responseTimeMs,
                'json_error' => json_last_error_msg(),
                'body_excerpt' => $this->excerpt($response->body()),
            ], $responseTimeMs, $httpStatusCode);
        }

        try {
            $snapshot = $this->storeSnapshot($site, $payload, $httpStatusCode, $responseTimeMs);
            $pack = Arr::get($payload, 'instance.pack');

            $siteUpdates = [
                'last_sync_at' => now(),
                'last_success_at' => now(),
                'consecutive_failures' => 0,
            ];

            if ($pack !== null && $pack !== '' && is_numeric($pack)) {
                $siteUpdates['pack'] = (int) $pack;
            }

            $site->forceFill($siteUpdates)->save();
        } catch (Throwable $e) {
            return $this->fail($site, 'SNAPSHOT_STORE_FAILED', 'Report response was received but could not be saved.', [
                'endpoint' => $endpoint,
                'from' => $from,
                'to' => $to,
                'status' => $httpStatusCode,
                'response_time_ms' => $responseTimeMs,
                'exception' => class_basename($e),
                'exception_message' => $this->excerpt($e->getMessage()),
            ], $responseTimeMs, $httpStatusCode);
        }

        $this->logInfo('sync succeeded', [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'http_status_code' => $httpStatusCode,
            'response_time_ms' => $responseTimeMs,
            'snapshot_id' => $snapshot->id,
        ]);

        return [
            'ok' => true,
            'message' => 'Report synchronized.',
            'snapshot' => $snapshot,
            'response_time_ms' => $responseTimeMs,
            'http_status_code' => $httpStatusCode,
        ];
    }

    private function storeSnapshot(Site $site, array $payload, int $httpStatusCode, int $responseTimeMs): ReportSnapshot
    {
        $warnings = Arr::get($payload, 'data_warnings', []);
        $warnings = is_array($warnings) ? array_values($warnings) : [];

        return $site->reportSnapshots()->create([
            'period_from' => Arr::get($payload, 'period.from'),
            'period_to' => Arr::get($payload, 'period.to'),
            'api_version' => Arr::get($payload, 'api_version'),
            'revenue_unit' => Arr::get($payload, 'revenue_unit'),
            'payload' => $payload,
            'data_warnings' => $warnings,
            'has_warnings' => count($warnings) > 0,
            'http_status_code' => $httpStatusCode,
            'response_time_ms' => $responseTimeMs,
            // V2 usa periods.all_time.*, V1 usa orders.total / reservations.total
            'orders_total' => $this->nullableInteger(
                Arr::get($payload, 'periods.all_time.orders_total')
                ?? Arr::get($payload, 'orders.total')
            ),
            'orders_revenue' => $this->ordersRevenueForSnapshot($payload),
            'reservations_total' => $this->nullableInteger(
                Arr::get($payload, 'periods.all_time.reservations_total')
                ?? Arr::get($payload, 'reservations.total')
            ),
            'reservations_covers' => $this->nullableInteger(
                Arr::get($payload, 'periods.all_time.total_covers')
                ?? Arr::get($payload, 'reservations.total_covers')
            ),
            // Colonne per-periodo: presenti solo nel payload V2 (chiave "periods").
            // nullableInteger(null) restituisce null, quindi i payload V1 restano compatibili.
            'orders_today'              => $this->nullableInteger(Arr::get($payload, 'periods.today.orders_total')),
            'reservations_today'        => $this->nullableInteger(Arr::get($payload, 'periods.today.reservations_total')),
            'orders_last_7_days'        => $this->nullableInteger(Arr::get($payload, 'periods.last_7_days.orders_total')),
            'reservations_last_7_days'  => $this->nullableInteger(Arr::get($payload, 'periods.last_7_days.reservations_total')),
            'orders_last_30_days'       => $this->nullableInteger(Arr::get($payload, 'periods.last_30_days.orders_total')),
            'reservations_last_30_days' => $this->nullableInteger(Arr::get($payload, 'periods.last_30_days.reservations_total')),
            'orders_current_month'       => $this->nullableInteger(Arr::get($payload, 'periods.current_month.orders_total')),
            'reservations_current_month' => $this->nullableInteger(Arr::get($payload, 'periods.current_month.reservations_total')),
            'fetched_at' => now(),
        ]);
    }

    private function fail(
        Site $site,
        string $code,
        string $message,
        array $context,
        ?int $responseTimeMs = null,
        ?int $httpStatusCode = null
    ): array {
        $occurredAt = now();
        $consecutiveFailures = ((int) $site->consecutive_failures) + 1;

        $site->forceFill([
            'last_sync_at' => $occurredAt,
            'last_error_at' => $occurredAt,
            'consecutive_failures' => $consecutiveFailures,
        ])->save();

        $error = $site->syncErrors()->create([
            'code' => $code,
            'http_status_code' => $httpStatusCode,
            'message' => $message,
            'context' => $this->sanitizeContext($context),
            'consecutive_failures' => $consecutiveFailures,
            'occurred_at' => $occurredAt,
        ]);

        $this->logWarning('sync failed', [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'code' => $code,
            'http_status_code' => $httpStatusCode,
            'message' => $message,
            'response_time_ms' => $responseTimeMs,
            'consecutive_failures' => $consecutiveFailures,
        ]);

        return [
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'error' => $error,
            'response_time_ms' => $responseTimeMs,
            'http_status_code' => $httpStatusCode,
        ];
    }

    /**
     * Determina il periodo da richiedere per la prossima sync di un sito.
     *
     * - Nessun snapshot precedente  → from=2000-01-01, to=oggi  (tutta la storia)
     * - Snapshot presente           → from=period_to+1gg, to=oggi  (incrementale)
     * - Già aggiornato ad oggi      → from=oggi, to=oggi  (sync giornaliera)
     *
     * I blocchi "periods", "daily" e "usage" del payload V2 vengono sempre
     * calcolati indipendentemente dal range passato, quindi periods.all_time
     * contiene sempre il totale storico completo a prescindere da from/to.
     *
     * @return array{from: string, to: string, reason: string}
     */
    public function determineSyncPeriod(Site $site): array
    {
        $today  = \Carbon\Carbon::today()->toDateString();
        $snap   = $site->latestSnapshot;

        if (! $snap) {
            return ['from' => '2000-01-01', 'to' => $today, 'reason' => 'initial'];
        }

        // Usa period_to se valorizzato, altrimenti la data di fetch come proxy.
        $lastDate = $snap->period_to
            ? $snap->period_to->toDateString()
            : ($snap->fetched_at ? $snap->fetched_at->toDateString() : $today);

        $nextFrom = \Carbon\Carbon::parse($lastDate)->addDay()->toDateString();

        if ($nextFrom >= $today) {
            // Già aggiornato: sincronizza comunque oggi per avere periods.today aggiornato.
            return ['from' => $today, 'to' => $today, 'reason' => 'current'];
        }

        return ['from' => $nextFrom, 'to' => $today, 'reason' => 'incremental'];
    }

    private function endpointFor(Site $site): string
    {
        return rtrim($site->url, '/') . '/api/private/report-summary';
    }

    private function isHttpsUrl(string $url): bool
    {
        return str_starts_with(strtolower($url), 'https://');
    }

    private function responseTimeMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function nullableInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }

    private function ordersRevenueForSnapshot(array $payload): ?int
    {
        // Salva sempre il valore grezzo se presente — l'unità è già in revenue_unit
        // e viene gestita da resolveRevenue() al momento della lettura.
        $raw = Arr::get($payload, 'periods.all_time.revenue_confirmed')
            ?? Arr::get($payload, 'orders.revenue_confirmed');

        return $this->nullableInteger($raw);
    }

    private function sanitizeContext(array $context): array
    {
        unset($context['token'], $context['authorization'], $context['headers']);

        return $context;
    }

    private function excerpt(string $value): string
    {
        return substr($value, 0, 1000);
    }

    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('reports_sync')->info('[reports-sync] ' . $message, $context);
    }

    private function logWarning(string $message, array $context = []): void
    {
        Log::channel('reports_sync')->warning('[reports-sync] ' . $message, $context);
    }
}
