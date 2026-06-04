<?php

namespace Tests\Feature;

use App\Models\BackofficeSetting;
use App\Models\ReportSnapshot;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackofficeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backoffice_can_update_savings_benchmark(): void
    {
        $this
            ->withSession(['backoffice_authenticated' => true])
            ->post(route('backoffice-settings.update'), [
                'order_commission_percent' => 10,
                'reservation_cover_fee' => 2.5,
            ])
            ->assertRedirect(route('backoffice-settings.edit'));

        $this->assertSame(
            '10',
            BackofficeSetting::query()->where('key', 'savings_order_commission_percent')->value('value')
        );
        $this->assertSame(
            '2.5',
            BackofficeSetting::query()->where('key', 'savings_reservation_cover_fee')->value('value')
        );
    }

    public function test_dashboard_uses_configured_savings_benchmark(): void
    {
        $site = Site::create([
            'name' => 'Alpha',
            'sort_order' => 1,
            'url' => 'https://alpha.test',
            'token' => 'alpha-token',
            'active' => true,
            'last_success_at' => now(),
        ]);

        ReportSnapshot::create([
            'site_id' => $site->id,
            'period_from' => '2026-06-01',
            'period_to' => '2026-06-30',
            'api_version' => '2',
            'revenue_unit' => 'euros',
            'payload' => [
                'periods' => [
                    'all_time' => [
                        'orders_total' => 20,
                        'orders_revenue' => 1000,
                        'orders_active_months' => 1,
                        'reservations_total' => 4,
                        'reservations_covers' => 10,
                        'reservations_active_months' => 1,
                    ],
                ],
            ],
            'fetched_at' => now(),
        ]);

        BackofficeSetting::create(['key' => 'savings_order_commission_percent', 'value' => '10']);
        BackofficeSetting::create(['key' => 'savings_reservation_cover_fee', 'value' => '2.5']);

        $this
            ->withSession(['backoffice_authenticated' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Risparmio stimato Future Plus')
            ->assertSee('€ 125.00')
            ->assertSee('Just Eat/Deliveroo/Glovo 10%')
            ->assertSee('TheFork € 2,50/coperto');
    }
}
