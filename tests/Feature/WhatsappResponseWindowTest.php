<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Models\Setting;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class WhatsappResponseWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);
    }

    public function test_controllers_treat_missing_whatsapp_response_dates_as_outside_the_24_hour_window(): void
    {
        Setting::query()
            ->where('name', 'wa')
            ->firstOrFail()
            ->update([
                'property' => json_encode([
                    'numbers' => ['393271622244'],
                ]),
            ]);

        foreach ($this->controllersUsingWhatsappResponseWindow() as $controllerClass) {
            $controller = app($controllerClass);
            $method = new ReflectionMethod($controllerClass, 'isLastResponseWaWithin24Hours');
            $method->setAccessible(true);

            $this->assertFalse($method->invoke($controller, 0), $controllerClass . ' first number');
            $this->assertFalse($method->invoke($controller, 1), $controllerClass . ' second number');
        }
    }

    public function test_controllers_treat_invalid_whatsapp_response_dates_as_outside_the_24_hour_window(): void
    {
        Setting::query()
            ->where('name', 'wa')
            ->firstOrFail()
            ->update([
                'property' => json_encode([
                    'last_response_wa_1' => ['not-a-date'],
                    'last_response_wa_2' => 'definitely-not-a-date',
                    'numbers' => ['393271622244'],
                ]),
            ]);

        foreach ($this->controllersUsingWhatsappResponseWindow() as $controllerClass) {
            $controller = app($controllerClass);
            $method = new ReflectionMethod($controllerClass, 'isLastResponseWaWithin24Hours');
            $method->setAccessible(true);

            $this->assertFalse($method->invoke($controller, 0), $controllerClass . ' first number');
            $this->assertFalse($method->invoke($controller, 1), $controllerClass . ' second number');
        }
    }

    public function test_order_whatsapp_fallback_treats_null_preference_as_template_first(): void
    {
        Http::fake([
            '*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.test-template'],
                ],
            ], 200),
        ]);

        $controller = app(OrderController::class);
        $method = new ReflectionMethod(OrderController::class, 'sendWhatsappMessageWithFallback');
        $method->setAccessible(true);

        $sentMessage = $method->invoke(
            $controller,
            'https://graph.facebook.com/v24.0/test/messages',
            '393271622244',
            ['messaging_product' => 'whatsapp', 'to' => '393271622244', 'type' => 'interactive'],
            ['messaging_product' => 'whatsapp', 'to' => '393271622244', 'type' => 'template'],
            null
        );

        $this->assertSame([
            'message_id' => 'wamid.test-template',
            'type_flag' => 1,
        ], $sentMessage);

        Http::assertSent(fn ($request) => $request['type'] === 'template');
    }

    private function controllersUsingWhatsappResponseWindow(): array
    {
        return [
            OrderController::class,
            ReservationController::class,
            SettingController::class,
            StripeWebhookController::class,
        ];
    }
}
