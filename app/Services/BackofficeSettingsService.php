<?php

namespace App\Services;

use App\Models\BackofficeSetting;
use Illuminate\Support\Facades\Schema;

class BackofficeSettingsService
{
    private const ORDER_COMMISSION_PERCENT_KEY = 'savings_order_commission_percent';

    private const RESERVATION_COVER_FEE_KEY = 'savings_reservation_cover_fee';

    public function settingsTableExists(): bool
    {
        return Schema::hasTable('backoffice_settings');
    }

    /**
     * @return array{
     *     order_commission_rate: float,
     *     order_commission_percent: float,
     *     reservation_cover_fee: float
     * }
     */
    public function savingsBenchmark(): array
    {
        $orderCommissionPercent = $this->floatSetting(
            self::ORDER_COMMISSION_PERCENT_KEY,
            SiteMonthlyMetricsService::ORDER_MARKETPLACE_COMMISSION_RATE * 100
        );
        $reservationCoverFee = $this->floatSetting(
            self::RESERVATION_COVER_FEE_KEY,
            SiteMonthlyMetricsService::RESERVATION_MARKETPLACE_COVER_FEE
        );
        $orderCommissionPercent = min(100.0, max(0.0, $orderCommissionPercent));
        $reservationCoverFee = max(0.0, $reservationCoverFee);

        return [
            'order_commission_rate' => round($orderCommissionPercent / 100, 4),
            'order_commission_percent' => $orderCommissionPercent,
            'reservation_cover_fee' => $reservationCoverFee,
        ];
    }

    public function updateSavingsBenchmark(float $orderCommissionPercent, float $reservationCoverFee): void
    {
        if (! $this->settingsTableExists()) {
            return;
        }

        $this->putFloat(self::ORDER_COMMISSION_PERCENT_KEY, $orderCommissionPercent);
        $this->putFloat(self::RESERVATION_COVER_FEE_KEY, $reservationCoverFee);
    }

    private function floatSetting(string $key, float $default): float
    {
        if (! $this->settingsTableExists()) {
            return $default;
        }

        $value = BackofficeSetting::query()->where('key', $key)->value('value');

        return is_numeric($value) ? (float) $value : $default;
    }

    private function putFloat(string $key, float $value): void
    {
        BackofficeSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) round($value, 4)]
        );
    }
}
