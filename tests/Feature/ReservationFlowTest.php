<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ReservationController as ApiReservationController;
use App\Mail\FailureAlertMail;
use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\Marketing\CustomerPromotionService;
use App\Services\Marketing\ReservationPromotionApplicationService;
use App\Services\CustomerAuth\VerifiedCheckoutSessionService;
use Carbon\Carbon;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);

        config([
            'configurazione.APP_URL' => 'http://localhost',
            'configurazione.WA_ID' => '123456789',
            'configurazione.WA_TO' => 'Bearer test-token',
        ]);

        $controller = \Mockery::mock(ApiReservationController::class, [
            app(VerifiedCheckoutSessionService::class),
            app(\App\Services\CustomerAuth\CustomerAccessService::class),
            app(ReservationPromotionApplicationService::class),
            app(CustomerPromotionService::class),
        ])->makePartial();

        $controller->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('save_message')->andReturn([]);
        $controller->shouldReceive('send_mail')->andReturnNull();

        $this->app->instance(ApiReservationController::class, $controller);
    }

    public function test_reservation_store_rejects_a_day_marked_as_day_off(): void
    {
        Http::fake();

        $slot = Carbon::now()->addDays(7)->setTime(19, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'day_off' => [$slot->format('Y-m-d')],
            'max_table' => 6,
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'r' => '56',
            ]);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_reservation_store_sends_failure_alert_when_slot_is_not_available(): void
    {
        Http::fake();
        Mail::fake();

        $slot = Carbon::now()->addDays(7)->setTime(20, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'day_off' => [$slot->format('Y-m-d')],
            'max_table' => 6,
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'email' => 'alert-reservation@example.com',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'r' => '56',
            ]);

        Mail::assertSent(FailureAlertMail::class, function (FailureAlertMail $mail) {
            return $mail->hasTo('info@future-plus.it')
                && $mail->alert['flow'] === 'reservation'
                && $mail->alert['error']['type'] === 'availability_changed'
                && ($mail->alert['customer']['email'] ?? null) === 'alert-reservation@example.com';
        });
    }

    public function test_reservation_store_counts_existing_reservations_for_the_same_slot(): void
    {
        Http::fake();

        $slot = Carbon::now()->addDays(8)->setTime(20, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 4,
        ]);

        DB::table('reservations')->insert([
            'date_slot' => $slot->format('d/m/Y H:i'),
            'status' => 2,
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '3331111111',
            'n_person' => json_encode(['adult' => 4, 'child' => 0]),
            'sala' => null,
            'message' => null,
            'whatsapp_message_id' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'email' => 'anna@example.com',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'r' => '86',
            ]);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_reservation_store_accepts_slots_saved_as_string_service_ids(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.string-slot.1'],
                ],
            ], 200),
        ]);

        $slot = Carbon::now()->addDays(9)->setTime(19, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 6,
        ]);

        $setting = Setting::query()->where('name', 'advanced')->firstOrFail();
        $property = json_decode($setting->property, true);
        $dayOfWeek = (int) $slot->format('N');
        $property['week_set'][$dayOfWeek][$slot->format('H:i')] = ['1'];

        $setting->update([
            'property' => json_encode($property),
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'email' => 'string-slot@example.com',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_reservation_store_uses_room_specific_capacity_when_double_room_is_enabled(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.room.1'],
                ],
            ], 200),
        ]);

        $slot = Carbon::now()->addDays(9)->setTime(21, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'dt' => true,
            'max_table' => 0,
            'max_table_1' => 2,
            'max_table_2' => 6,
            'sala_1' => 'Interno',
            'sala_2' => 'Esterno',
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'sala' => 1,
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $reservation = Reservation::query()->sole();

        $this->assertSame('1', (string) $reservation->sala);
    }

    public function test_reservation_store_retries_with_template_when_interactive_message_fails(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::sequence()
                ->push([
                    'messages' => [
                        ['id' => 'wamid.first'],
                    ],
                ], 200)
                ->push([
                    'error' => [
                        'message' => 'Second number failed',
                    ],
                ], 500)
                ->push([
                    'messages' => [
                        ['id' => 'wamid.second.template'],
                    ],
                ], 200),
        ]);

        $slot = Carbon::now()->addDays(10)->setTime(19, 30)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 6,
        ]);
        $this->updateWhatsappNumbers(['39000111000', '39000111001'], true);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $reservation = Reservation::query()->sole();

        $this->assertSame(
            ['wamid.first', 'wamid.second.template'],
            json_decode($reservation->whatsapp_message_id, true)
        );
        Http::assertSentCount(3);
    }

    public function test_table_customer_promotion_is_marked_used_when_reservation_is_created(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.reservation.promo'],
                ],
            ], 200),
        ]);

        $customer = $this->createCustomer('reservation-promo@example.com');
        Sanctum::actingAs($customer);
        $promotion = $this->createPromotion([
            'case_use' => 'table',
            'type_discount' => 'gift',
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = Carbon::now()->addDays(11)->setTime(19, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 6,
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'customer_promotion_id' => $customerPromotion->id,
            'email' => $customer->email,
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $reservation = Reservation::query()->sole();
        $customerPromotion->refresh();

        $this->assertSame($reservation->id, $customerPromotion->reservation_id);
        $this->assertNull($customerPromotion->order_id);
        $this->assertEquals(0.0, (float) $customerPromotion->discount_amount);
        $this->assertNotNull($customerPromotion->promo_used);
        $this->assertSame('used', $customerPromotion->status);
        $this->assertSame('reservation_checkout', $customerPromotion->metadata['applied_from'] ?? null);
        $this->assertSame(2, $customerPromotion->metadata['reservation_people'] ?? null);
    }

    public function test_take_away_customer_promotion_does_not_block_reservation_and_is_not_used(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.reservation.no-promo'],
                ],
            ], 200),
        ]);

        $customer = $this->createCustomer('reservation-takeaway-promo@example.com');
        Sanctum::actingAs($customer);
        $promotion = $this->createPromotion([
            'case_use' => 'take_away',
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = Carbon::now()->addDays(12)->setTime(19, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 6,
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'customer_promotion_id' => $customerPromotion->id,
            'email' => $customer->email,
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('reservations', 1);
        $customerPromotion->refresh();
        $this->assertNull($customerPromotion->reservation_id);
        $this->assertNull($customerPromotion->promo_used);
        $this->assertSame('assigned', $customerPromotion->status);
    }

    public function test_other_customer_promotion_does_not_block_reservation_and_is_not_used(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.reservation.other-promo'],
                ],
            ], 200),
        ]);

        $customer = $this->createCustomer('reservation-main@example.com');
        $otherCustomer = $this->createCustomer('reservation-other@example.com');
        Sanctum::actingAs($customer);
        $promotion = $this->createPromotion([
            'case_use' => 'table',
        ]);
        $customerPromotion = $this->assignPromotion($otherCustomer, $promotion);
        $slot = Carbon::now()->addDays(13)->setTime(19, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 6,
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'customer_promotion_id' => $customerPromotion->id,
            'email' => $customer->email,
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('reservations', 1);
        $customerPromotion->refresh();
        $this->assertNull($customerPromotion->reservation_id);
        $this->assertNull($customerPromotion->promo_used);
        $this->assertSame('assigned', $customerPromotion->status);
    }

    public function test_reservation_without_customer_promotion_remains_unchanged(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.reservation.without-promo'],
                ],
            ], 200),
        ]);

        $customer = $this->createCustomer('reservation-without-promo@example.com');
        Sanctum::actingAs($customer);
        $slot = Carbon::now()->addDays(14)->setTime(19, 0)->startOfMinute();
        $this->configureReservationSlot($slot, [
            'max_table' => 6,
        ]);

        $response = $this->postJson('/api/reservations', $this->reservationPayload($slot, [
            'email' => $customer->email,
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('customer_promotion', 0);
    }

    private function reservationPayload(Carbon $slot, array $overrides = []): array
    {
        $email = $overrides['email'] ?? 'cliente@example.com';
        $verifiedSession = app(VerifiedCheckoutSessionService::class)->issue($email);

        return array_merge([
            'name' => 'Anna',
            'surname' => 'Bianchi',
            'phone' => '3331234567',
            'email' => $email,
            'n_adult' => '2',
            'n_child' => '0',
            'message' => 'Vicino alla finestra',
            'news_letter' => false,
            'save_details' => false,
            'lang' => 'it',
            'date_slot' => $slot->format('Y-m-d H:i'),
            'verified_session_token' => $verifiedSession['token'],
        ], $overrides);
    }

    private function configureReservationSlot(Carbon $slot, array $overrides = []): void
    {
        $setting = Setting::query()->where('name', 'advanced')->firstOrFail();
        $property = json_decode($setting->property, true);
        $dayOfWeek = (int) $slot->format('N');

        $property['week_set'][$dayOfWeek] = [
            $slot->format('H:i') => [1],
        ];
        $property['day_off'] = [];
        $property['dt'] = false;
        $property['max_table'] = 6;
        $property['max_table_1'] = 0;
        $property['max_table_2'] = 0;
        $property['max_day_res'] = max((int) ($property['max_day_res'] ?? 30), Carbon::now()->diffInDays($slot) + 2);

        $setting->update([
            'property' => json_encode(array_replace_recursive($property, $overrides)),
        ]);
    }

    private function updateWhatsappNumbers(array $numbers, bool $within24Hours = false): void
    {
        $setting = Setting::query()->where('name', 'wa')->firstOrFail();
        $property = json_decode($setting->property, true);

        $property['numbers'] = $numbers;
        $reference = $within24Hours ? Carbon::now()->subHour() : Carbon::now()->subDays(2);
        $property['last_response_wa_1'] = $reference->toDateTimeString();
        $property['last_response_wa_2'] = $reference->toDateTimeString();

        $setting->update([
            'property' => json_encode($property),
        ]);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'name' => 'Reservation',
            'surname' => 'Customer',
            'email' => $email,
            'phone' => '3331112222',
        ]);
    }

    private function createPromotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Promo prenotazione',
            'slug' => 'promo-reservation-' . Str::uuid(),
            'status' => 'active',
            'case_use' => 'table',
            'type_discount' => 'fixed',
            'discount' => 0,
            'minimum_pretest' => null,
            'permanent' => true,
            'schedule_at' => null,
            'expiring_at' => null,
        ], $attributes));
    }

    private function assignPromotion(Customer $customer, Promotion $promotion, array $attributes = []): CustomerPromotion
    {
        return CustomerPromotion::query()->create(array_merge([
            'customer_id' => $customer->id,
            'promotion_id' => $promotion->id,
            'tracking_token' => (string) Str::uuid(),
            'status' => 'assigned',
        ], $attributes));
    }
}
