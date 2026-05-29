<?php

namespace Tests\Feature;

use App\Mail\FailureAlertMail;
use App\Models\Setting;
use App\Services\CustomerAuth\VerifiedCheckoutSessionService;
use Carbon\Carbon;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderFailureAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);
    }

    public function test_order_store_sends_failure_alert_when_slot_is_not_available(): void
    {
        Mail::fake();

        $slot = Carbon::now()->addDays(7)->setTime(19, 0)->startOfMinute();
        $this->configureOrderSlot($slot, [
            'max_asporto' => 0,
        ]);

        $response = $this->postJson('/api/orders', $this->orderPayload($slot, [
            'email' => 'alert-order@example.com',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
            ]);

        Mail::assertSent(FailureAlertMail::class, function (FailureAlertMail $mail) {
            return $mail->hasTo('info@future-plus.it')
                && $mail->alert['flow'] === 'order'
                && $mail->alert['error']['type'] === 'availability_changed'
                && ($mail->alert['customer']['email'] ?? null) === 'alert-order@example.com';
        });
    }

    public function test_order_store_sends_failure_alert_on_validation_failure(): void
    {
        Mail::fake();

        $slot = Carbon::now()->addDays(8)->setTime(20, 0)->startOfMinute();

        $response = $this->postJson('/api/orders', $this->orderPayload($slot, [
            'name' => '',
            'email' => 'validation-order@example.com',
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        Mail::assertSent(FailureAlertMail::class, function (FailureAlertMail $mail) {
            return $mail->hasTo('info@future-plus.it')
                && $mail->alert['flow'] === 'order'
                && $mail->alert['error']['type'] === 'validation_failure'
                && isset($mail->alert['error']['details']['validation_errors']['name']);
        });
    }

    private function orderPayload(Carbon $slot, array $overrides = []): array
    {
        $email = $overrides['email'] ?? 'cliente-ordine@example.com';
        $verifiedSession = app(VerifiedCheckoutSessionService::class)->issue($email);

        return array_merge([
            'name' => 'Luca',
            'surname' => 'Verdi',
            'phone' => '3331234567',
            'email' => $email,
            'message' => 'Citofonare',
            'news_letter' => false,
            'save_details' => false,
            'lang' => 'it',
            'date_slot' => $slot->format('Y-m-d H:i'),
            'verified_session_token' => $verifiedSession['token'],
            'paying' => false,
            'cart' => [
                'products' => [],
                'menus' => [],
            ],
        ], $overrides);
    }

    private function configureOrderSlot(Carbon $slot, array $overrides = []): void
    {
        $setting = Setting::query()->where('name', 'advanced')->firstOrFail();
        $property = json_decode($setting->property, true);
        $dayOfWeek = (int) $slot->format('N');

        $property['week_set'][$dayOfWeek] = [
            $slot->format('H:i') => [2],
        ];
        $property['day_off'] = [];
        $property['max_asporto'] = 4;
        $property['max_domicilio'] = 4;

        $setting->update([
            'property' => json_encode(array_replace_recursive($property, $overrides)),
        ]);
    }
}
