<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Services\Marketing\PromotionNotificationFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromotionNotificationFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_formatter_returns_order_promotion_with_discount_amount(): void
    {
        $customer = $this->createCustomer('notification-order@example.com');
        $order = $this->createOrder($customer, 25.0);
        $promotion = $this->createPromotion([
            'name' => 'Sconto ordine',
            'case_use' => 'take_away',
            'type_discount' => 'fixed',
            'discount' => 5,
        ]);

        $customerPromotion = $this->assignPromotion($customer, $promotion, [
            'order_id' => $order->id,
            'discount_amount' => 5,
            'promo_used' => now(),
            'status' => 'used',
            'metadata' => [
                'affected_items' => [
                    ['type' => 'product', 'name' => 'Pizza test'],
                ],
            ],
        ]);

        $formatted = $this->formatter()->forOrder($order);

        $this->assertCount(1, $formatted);
        $this->assertSame($customerPromotion->id, $formatted[0]['customer_promotion_id']);
        $this->assertSame('Sconto ordine', $formatted[0]['promotion_name']);
        $this->assertSame('fixed', $formatted[0]['type_discount']);
        $this->assertEquals(5.0, $formatted[0]['discount_amount']);
        $this->assertStringContainsString('Sconto applicato', $formatted[0]['label']);
        $this->assertSame('Pizza test', $formatted[0]['affected_items'][0]['name']);
    }

    public function test_formatter_returns_reservation_promotion(): void
    {
        $customer = $this->createCustomer('notification-reservation@example.com');
        $reservation = $this->createReservation($customer);
        $promotion = $this->createPromotion([
            'name' => 'Calice al tavolo',
            'case_use' => 'table',
            'type_discount' => 'gift',
            'discount' => 0,
        ]);

        $this->assignPromotion($customer, $promotion, [
            'reservation_id' => $reservation->id,
            'discount_amount' => 0,
            'promo_used' => now(),
            'status' => 'used',
            'metadata' => [
                'affected_items' => [
                    ['type' => 'reservation', 'gift_benefit' => true],
                ],
            ],
        ]);

        $formatted = $this->formatter()->forReservation($reservation);

        $this->assertCount(1, $formatted);
        $this->assertSame('Calice al tavolo', $formatted[0]['promotion_name']);
        $this->assertSame('gift', $formatted[0]['type_discount']);
        $this->assertEquals(0.0, $formatted[0]['discount_amount']);
        $this->assertSame('Promozione tavolo attivata: Calice al tavolo', $formatted[0]['label']);
    }

    public function test_order_product_fixed_discount_produces_line_annotation(): void
    {
        $customer = $this->createCustomer('notification-annotation-fixed@example.com');
        $order = $this->createOrder($customer, 20.0);
        $promotion = $this->createPromotion([
            'name' => 'Cinque euro',
            'case_use' => 'take_away',
            'type_discount' => 'fixed',
            'discount' => 5,
        ]);

        $this->assignPromotion($customer, $promotion, [
            'order_id' => $order->id,
            'discount_amount' => 5,
            'promo_used' => now(),
            'status' => 'used',
            'metadata' => [
                'affected_items' => [
                    [
                        'type' => 'product',
                        'id' => 123,
                        'name' => 'Margherita',
                        'quantity' => 1,
                        'discount_amount' => 5,
                        'cart_index' => 0,
                    ],
                ],
            ],
        ]);

        $annotations = $this->formatter()->annotationsForOrder($order);

        $this->assertSame('🎁 con promozione -€5,00', $this->formatter()->annotationForItem($annotations, 'product', 123));
        $this->assertSame('🎁 con promozione -€5,00', $this->formatter()->annotationForItem($annotations, 'product', 123, 0));
    }

    public function test_order_gift_discount_produces_omaggio_annotation(): void
    {
        $customer = $this->createCustomer('notification-annotation-gift@example.com');
        $order = $this->createOrder($customer, 12.0);
        $promotion = $this->createPromotion([
            'name' => 'Dolce omaggio',
            'case_use' => 'take_away',
            'type_discount' => 'gift',
            'discount' => 0,
        ]);

        $this->assignPromotion($customer, $promotion, [
            'order_id' => $order->id,
            'discount_amount' => 4,
            'promo_used' => now(),
            'status' => 'used',
            'metadata' => [
                'affected_items' => [
                    [
                        'type' => 'product',
                        'id' => 456,
                        'name' => 'Tiramisù',
                        'quantity' => 1,
                        'discount_amount' => 4,
                        'gift_quantity' => 1,
                    ],
                ],
            ],
        ]);

        $annotations = $this->formatter()->annotationsForOrder($order);

        $this->assertSame('🎁 Omaggio', $this->formatter()->annotationForItem($annotations, 'product', 456));
    }

    public function test_reservation_whatsapp_text_mentions_active_reservation_promotion(): void
    {
        $customer = $this->createCustomer('notification-reservation-whatsapp@example.com');
        $reservation = $this->createReservation($customer);
        $promotion = $this->createPromotion([
            'name' => 'Aperitivo tavolo',
            'case_use' => 'table',
            'type_discount' => 'gift',
            'discount' => 0,
        ]);

        $this->assignPromotion($customer, $promotion, [
            'reservation_id' => $reservation->id,
            'discount_amount' => 0,
            'promo_used' => now(),
            'status' => 'used',
            'metadata' => [
                'affected_items' => [
                    ['type' => 'reservation', 'gift_benefit' => true],
                ],
            ],
        ]);

        $text = $this->formatter()->whatsappTextForReservation($reservation);

        $this->assertStringContainsString('🎁 Promozione prenotazione: Aperitivo tavolo', $text);
        $this->assertStringNotContainsString('Sconto 0', $text);
    }

    public function test_formatter_returns_empty_array_without_applied_promotion(): void
    {
        $customer = $this->createCustomer('notification-empty@example.com');
        $order = $this->createOrder($customer, 30.0);

        $this->assertSame([], $this->formatter()->forOrder($order));
        $this->assertSame([], $this->formatter()->annotationsForOrder($order));
    }

    private function formatter(): PromotionNotificationFormatter
    {
        return app(PromotionNotificationFormatter::class);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'name' => 'Notification',
            'surname' => 'Customer',
            'email' => $email,
            'phone' => '3331112222',
        ]);
    }

    private function createOrder(Customer $customer, float $total): Order
    {
        $orderId = $this->insertRow('orders', [
            'customer_id' => $customer->id,
            'date_slot' => '01/06/2026 19:00',
            'status' => 4,
            'name' => $customer->name,
            'surname' => $customer->surname,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'checkout_session_id' => null,
            'address' => null,
            'address_n' => null,
            'comune' => null,
            'whatsapp_message_id' => null,
            'tot_price' => $total,
            'message' => null,
            'news_letter' => false,
            'notificated' => false,
        ]);

        return Order::query()->findOrFail($orderId);
    }

    private function createReservation(Customer $customer): Reservation
    {
        $reservationId = $this->insertRow('reservations', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'surname' => $customer->surname,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'date_slot' => '01/06/2026 20:00',
            'n_person' => json_encode(['adult' => 2, 'child' => 0]),
            'message' => null,
            'status' => 2,
            'news_letter' => false,
            'whatsapp_message_id' => null,
        ]);

        return Reservation::query()->findOrFail($reservationId);
    }

    private function createPromotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Promo notifica',
            'slug' => 'promo-notification-' . Str::uuid(),
            'status' => 'active',
            'case_use' => 'generic',
            'discount' => 0,
            'type_discount' => 'fixed',
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

    private function insertRow(string $table, array $attributes): int
    {
        $now = now();
        $columns = array_flip(Schema::getColumnListing($table));

        $attributes = array_intersect_key(array_merge($attributes, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $columns);

        return (int) DB::table($table)->insertGetId($attributes);
    }
}
