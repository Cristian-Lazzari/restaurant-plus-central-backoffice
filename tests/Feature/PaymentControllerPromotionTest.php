<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PaymentController;
use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Order;
use App\Models\Promotion;
use App\Support\Currency;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionMethod;
use Tests\TestCase;

class PaymentControllerPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);
    }

    public function test_payment_payload_uses_discounted_order_total_and_promotion_metadata(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, 15);
        $promotion = $this->createPromotion();
        $customerPromotion = $this->createCustomerPromotion($customer, $promotion, $order, [
            'promo_used' => now(),
            'discount_amount' => 5,
            'metadata' => [
                'subtotal_before_discount' => 20,
                'discount_amount' => 5,
                'total_after_discount' => 15,
                'applied_from' => 'order_checkout',
            ],
        ]);

        $payload = $this->checkoutPayload($order->fresh(['customerPromotions.promotion']));

        $this->assertSame(Currency::toMinorUnits(15), $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('Ordine ristorante #' . $order->id, $payload['line_items'][0]['price_data']['product_data']['name']);
        $this->assertSame((string) $order->id, $payload['metadata']['order_id']);
        $this->assertSame((string) $customerPromotion->id, $payload['metadata']['customer_promotion_id']);
        $this->assertSame((string) $promotion->id, $payload['metadata']['promotion_id']);
        $this->assertSame('5.00', $payload['metadata']['discount_amount']);
        $this->assertSame('20.00', $payload['metadata']['subtotal_before_discount']);
        $this->assertSame('15.00', $payload['metadata']['total_after_discount']);
        $this->assertSame($payload['metadata'], $payload['payment_intent_data']['metadata']);
    }

    public function test_payment_payload_without_promotion_uses_full_order_total(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, 26);

        $payload = $this->checkoutPayload($order->fresh(['customerPromotions.promotion']));

        $this->assertSame(Currency::toMinorUnits(26), $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame((string) $order->id, $payload['metadata']['order_id']);
        $this->assertArrayNotHasKey('customer_promotion_id', $payload['metadata']);
        $this->assertArrayNotHasKey('discount_amount', $payload['metadata']);
    }

    public function test_payment_payload_ignores_unapplied_customer_promotion(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, 18);
        $promotion = $this->createPromotion();
        $customerPromotion = $this->createCustomerPromotion($customer, $promotion, null, [
            'discount_amount' => null,
            'promo_used' => null,
        ]);

        $payload = $this->checkoutPayload($order->fresh(['customerPromotions.promotion']));
        $customerPromotion->refresh();

        $this->assertSame(Currency::toMinorUnits(18), $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertArrayNotHasKey('customer_promotion_id', $payload['metadata']);
        $this->assertNull($customerPromotion->promo_used);
        $this->assertNull($customerPromotion->order_id);
    }

    public function test_gift_promotion_payload_uses_final_total_without_negative_amounts(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, 0.50);
        $promotion = $this->createPromotion([
            'type_discount' => 'gift',
            'discount' => null,
        ]);
        $this->createCustomerPromotion($customer, $promotion, $order, [
            'promo_used' => now(),
            'discount_amount' => 12,
            'metadata' => [
                'subtotal_before_discount' => 12.50,
                'discount_amount' => 12,
                'total_after_discount' => 0.50,
            ],
        ]);

        $payload = $this->checkoutPayload($order->fresh(['customerPromotions.promotion']));

        $this->assertSame(50, $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('0.50', $payload['metadata']['total_after_discount']);
    }

    public function test_order_total_below_stripe_minimum_is_rejected_before_session_creation(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('order_total_below_stripe_minimum');

        $this->checkoutPayload($order->fresh(['customerPromotions.promotion']));
    }

    private function checkoutPayload(Order $order): array
    {
        $method = new ReflectionMethod(PaymentController::class, 'checkoutSessionPayload');
        $method->setAccessible(true);

        return $method->invoke(app(PaymentController::class), $order);
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Stripe',
            'surname' => 'Customer',
            'email' => 'stripe-' . Str::uuid() . '@example.com',
            'phone' => '3331112222',
        ]);
    }

    private function createOrder(Customer $customer, float $total): Order
    {
        $orderId = DB::table('orders')->insertGetId([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Order::query()->findOrFail($orderId);
    }

    private function createPromotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Promo Stripe',
            'slug' => 'promo-stripe-' . Str::uuid(),
            'status' => 'active',
            'case_use' => 'take_away',
            'type_discount' => 'fixed',
            'discount' => 5,
            'minimum_pretest' => null,
            'permanent' => true,
        ], $attributes));
    }

    private function createCustomerPromotion(
        Customer $customer,
        Promotion $promotion,
        ?Order $order,
        array $attributes = []
    ): CustomerPromotion {
        return CustomerPromotion::query()->create(array_merge([
            'customer_id' => $customer->id,
            'promotion_id' => $promotion->id,
            'order_id' => $order?->id,
            'tracking_token' => (string) Str::uuid(),
            'status' => $order ? 'used' : 'assigned',
        ], $attributes));
    }
}
