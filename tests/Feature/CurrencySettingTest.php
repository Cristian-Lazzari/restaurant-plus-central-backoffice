<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Currency;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);
    }

    public function test_settings_api_exposes_configured_currency(): void
    {
        Setting::query()
            ->where('name', 'Valuta')
            ->firstOrFail()
            ->update([
                'property' => json_encode([
                    'code' => 'USD',
                    'symbol' => '$',
                    'label' => 'US Dollar',
                ]),
            ]);

        $this->getJson('/api/setting')
            ->assertOk()
            ->assertJsonPath('currency.code', 'USD')
            ->assertJsonPath('currency.symbol', '$')
            ->assertJsonPath('currency.label', 'US Dollar');
    }

    public function test_admin_can_update_currency_from_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.settings.updateAll'), [
                'defaultLang' => 'it',
                'currency_code' => 'CHF',
                'tavoli_status' => 1,
                'table_promo' => 0,
                'asporto_status' => 1,
                'asporto_pay' => 1,
                'min_price_a' => 0,
                'domicilio_status' => 1,
                'domicilio_pay' => 1,
                'min_price_d' => 0,
                'delivery_cost' => 0,
                'ferie_status' => 0,
            ])
            ->assertRedirect();

        $currency = json_decode(
            Setting::query()->where('name', 'Valuta')->firstOrFail()->property,
            true
        );

        $this->assertSame('CHF', $currency['code']);
        $this->assertSame('CHF', $currency['symbol']);
        $this->assertSame('Franco svizzero', $currency['label']);
        $this->assertSame(2, $currency['decimals']);
    }

    public function test_currency_helper_formats_cents_using_selected_setting(): void
    {
        Setting::query()
            ->where('name', 'Valuta')
            ->firstOrFail()
            ->update([
                'property' => json_encode([
                    'code' => 'CHF',
                    'symbol' => 'CHF',
                    'label' => 'Franco svizzero',
                ]),
            ]);

        $this->assertSame('CHF 12,50', Currency::formatCents(12.5));
    }

    public function test_currency_helper_formats_zero_decimal_currency_without_fraction_digits(): void
    {
        Setting::query()
            ->where('name', 'Valuta')
            ->firstOrFail()
            ->update([
                'property' => json_encode([
                    'code' => 'JPY',
                    'symbol' => '¥',
                    'label' => 'Yen giapponese',
                    'decimals' => 0,
                ]),
            ]);

        $this->assertSame('¥120', Currency::formatCents(120));
    }
}
