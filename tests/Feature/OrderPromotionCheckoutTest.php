<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OrderController;
use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionTarget;
use App\Services\CustomerAuth\CustomerAccessService;
use App\Services\CustomerAuth\VerifiedCheckoutSessionService;
use App\Services\Marketing\CustomerPromotionService;
use App\Services\Marketing\OrderPromotionApplicationService;
use Carbon\Carbon;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class OrderPromotionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);
        Mail::fake();
        $this->fakeOrderNotificationSideEffects();
    }

    public function test_order_with_product_fixed_customer_promotion_saves_discounted_total_and_marks_promo_used(): void
    {
        $customer = $this->createCustomer('fixed-order-promo@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 20);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = $this->availableSlot();

        $response = $this->actingAsCustomer($customer)
            ->postJson('/api/orders', $this->orderPayload($customer, $slot, [
                'customer_promotion_id' => $customerPromotion->id,
                'cart' => $this->cart([
                    $this->cartProduct($productId),
                ]),
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->firstOrFail();
        $customerPromotion->refresh();

        $this->assertEquals(15.0, $order->tot_price);
        $this->assertSame($order->id, $customerPromotion->order_id);
        $this->assertEquals(5.0, (float) $customerPromotion->discount_amount);
        $this->assertNotNull($customerPromotion->promo_used);
        $this->assertSame('used', $customerPromotion->status);
        $this->assertSame('order_checkout', $customerPromotion->metadata['applied_from'] ?? null);
        $this->assertEquals(20.0, $customerPromotion->metadata['subtotal_before_discount'] ?? null);
        $this->assertEquals(15.0, $customerPromotion->metadata['total_after_discount'] ?? null);
        $this->assertSame(1, CustomerPromotion::query()
            ->where('customer_id', $customer->id)
            ->where('promotion_id', $promotion->id)
            ->count());
    }

    public function test_reusable_order_customer_promotion_creates_fresh_available_assignment_after_use(): void
    {
        $customer = $this->createCustomer('reusable-order-promo@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 20);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
            'metadata' => [
                'reusable' => true,
            ],
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = $this->availableSlot();

        $response = $this->actingAsCustomer($customer)
            ->postJson('/api/orders', $this->orderPayload($customer, $slot, [
                'customer_promotion_id' => $customerPromotion->id,
                'cart' => $this->cart([
                    $this->cartProduct($productId),
                ]),
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->firstOrFail();
        $customerPromotion->refresh();
        $freshCustomerPromotion = CustomerPromotion::query()
            ->where('customer_id', $customer->id)
            ->where('promotion_id', $promotion->id)
            ->where('id', '!=', $customerPromotion->id)
            ->sole();

        $this->assertSame($order->id, $customerPromotion->order_id);
        $this->assertNotNull($customerPromotion->promo_used);
        $this->assertSame('used', $customerPromotion->status);
        $this->assertSame($freshCustomerPromotion->id, $customerPromotion->metadata['reusable_recreated_customer_promotion_id'] ?? null);

        $this->assertNull($freshCustomerPromotion->order_id);
        $this->assertNull($freshCustomerPromotion->reservation_id);
        $this->assertNull($freshCustomerPromotion->promo_used);
        $this->assertNull($freshCustomerPromotion->discount_amount);
        $this->assertSame('assigned', $freshCustomerPromotion->status);
        $this->assertNotSame($customerPromotion->tracking_token, $freshCustomerPromotion->tracking_token);
        $this->assertSame($customerPromotion->id, $freshCustomerPromotion->metadata['reusable_parent_id'] ?? null);
        $this->assertSame('reusable_promotion', $freshCustomerPromotion->metadata['source'] ?? null);
        $this->assertNotEmpty($freshCustomerPromotion->metadata['recreated_after_use_at'] ?? null);

        $offersResponse = $this->getJson('/api/auth/offers')->assertOk();

        $this->assertSame([$freshCustomerPromotion->id], array_column($offersResponse->json('available'), 'customer_promotion_id'));
        $this->assertSame([$customerPromotion->id], array_column($offersResponse->json('used'), 'customer_promotion_id'));
    }

    public function test_mark_used_reusable_customer_promotion_is_idempotent(): void
    {
        $customer = $this->createCustomer('reusable-idempotent@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 20);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
            'metadata' => [
                'reusable' => true,
            ],
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $service = app(CustomerPromotionService::class);

        $firstResult = $service->markUsed($customerPromotion, 5.0, null, null, [
            'affected_items' => [
                ['type' => 'product', 'id' => $productId],
            ],
        ]);
        $firstFreshId = $firstResult->metadata['reusable_recreated_customer_promotion_id'] ?? null;

        $secondResult = $service->markUsed($customerPromotion->fresh(), 5.0, null, null, [
            'affected_items' => [
                ['type' => 'product', 'id' => $productId],
            ],
        ]);

        $this->assertSame(2, CustomerPromotion::query()
            ->where('customer_id', $customer->id)
            ->where('promotion_id', $promotion->id)
            ->count());
        $this->assertSame($firstFreshId, $secondResult->metadata['reusable_recreated_customer_promotion_id'] ?? null);
        $this->assertSame(1, CustomerPromotion::query()
            ->where('customer_id', $customer->id)
            ->where('promotion_id', $promotion->id)
            ->whereNull('promo_used')
            ->where('status', '!=', 'used')
            ->count());
    }

    public function test_order_without_promotion_keeps_total_unchanged(): void
    {
        $customer = $this->createCustomer('no-order-promo@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 13);
        $slot = $this->availableSlot();

        $response = $this->actingAsCustomer($customer)
            ->postJson('/api/orders', $this->orderPayload($customer, $slot, [
                'cart' => $this->cart([
                    $this->cartProduct($productId, 2),
                ]),
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(26.0, Order::query()->firstOrFail()->tot_price);
        $this->assertSame(0, CustomerPromotion::query()->whereNotNull('promo_used')->count());
    }

    public function test_non_applicable_customer_promotion_does_not_block_order_and_is_not_marked_used(): void
    {
        $customer = $this->createCustomer('invalid-order-promo@example.com');
        $categoryId = $this->createCategory();
        $cartProductId = $this->createProduct($categoryId, 18);
        $targetProductId = $this->createProduct($categoryId, 30);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $targetProductId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = $this->availableSlot();

        $response = $this->actingAsCustomer($customer)
            ->postJson('/api/orders', $this->orderPayload($customer, $slot, [
                'customer_promotion_id' => $customerPromotion->id,
                'cart' => $this->cart([
                    $this->cartProduct($cartProductId),
                ]),
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->firstOrFail();
        $customerPromotion->refresh();

        $this->assertEquals(18.0, $order->tot_price);
        $this->assertNull($customerPromotion->order_id);
        $this->assertNull($customerPromotion->discount_amount);
        $this->assertNull($customerPromotion->promo_used);
        $this->assertSame('assigned', $customerPromotion->status);
    }

    public function test_gift_customer_promotion_discounts_one_target_unit(): void
    {
        $customer = $this->createCustomer('gift-order-promo@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 12);
        $promotion = $this->createPromotion([
            'type_discount' => 'gift',
            'discount' => null,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = $this->availableSlot();

        $response = $this->actingAsCustomer($customer)
            ->postJson('/api/orders', $this->orderPayload($customer, $slot, [
                'customer_promotion_id' => $customerPromotion->id,
                'cart' => $this->cart([
                    $this->cartProduct($productId, 2),
                ]),
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->firstOrFail();
        $customerPromotion->refresh();

        $this->assertEquals(12.0, $order->tot_price);
        $this->assertSame($order->id, $customerPromotion->order_id);
        $this->assertEquals(12.0, (float) $customerPromotion->discount_amount);
        $this->assertNotNull($customerPromotion->promo_used);
        $this->assertSame(1, $customerPromotion->metadata['affected_items'][0]['gift_quantity'] ?? null);
    }

    public function test_takeaway_order_minimum_uses_discounted_total_after_promotion(): void
    {
        $customer = $this->createCustomer('takeaway-discounted-minimum@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 20);
        $this->setOrderMinimum('Prenotazione Asporti', 18);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);
        $slot = $this->availableSlot();

        $response = $this->actingAsCustomer($customer)
            ->postJson('/api/orders', $this->orderPayload($customer, $slot, [
                'customer_promotion_id' => $customerPromotion->id,
                'cart' => $this->cart([
                    $this->cartProduct($productId),
                ]),
            ]));

        $response->assertOk()
            ->assertJson([
                'success' => false,
                'minimum_required' => 18,
                'minimum_missing' => 3,
                'minimum_checked_total' => 15,
            ]);

        $this->assertSame(0, Order::query()->count());
        $customerPromotion->refresh();
        $this->assertNull($customerPromotion->order_id);
        $this->assertNull($customerPromotion->promo_used);
    }

    private function fakeOrderNotificationSideEffects(): void
    {
        $controller = Mockery::mock(OrderController::class, [
            app(VerifiedCheckoutSessionService::class),
            app(CustomerAccessService::class),
            app(OrderPromotionApplicationService::class),
            app(CustomerPromotionService::class),
        ])->makePartial();

        $controller->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('sendWhatsappMessageWithFallback')
            ->zeroOrMoreTimes()
            ->andReturn([
                'message_id' => 'test-whatsapp-message-id',
                'type_flag' => 1,
            ]);
        $controller->shouldReceive('save_message')
            ->zeroOrMoreTimes()
            ->andReturn((object) ['id' => 1]);

        $this->app->instance(OrderController::class, $controller);
    }

    private function actingAsCustomer(Customer $customer): self
    {
        Sanctum::actingAs($customer);

        return $this;
    }

    private function orderPayload(Customer $customer, Carbon $slot, array $overrides = []): array
    {
        $verifiedSession = app(VerifiedCheckoutSessionService::class)->issue($customer->email);

        return array_merge([
            'name' => $customer->name,
            'surname' => $customer->surname,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'message' => 'Note test',
            'news_letter' => false,
            'save_details' => false,
            'lang' => 'it',
            'date_slot' => $slot->format('Y-m-d H:i'),
            'verified_session_token' => $verifiedSession['token'],
            'paying' => false,
            'cart' => $this->cart(),
        ], $overrides);
    }

    private function availableSlot(): Carbon
    {
        $slot = Carbon::now()->addDays(7)->setTime(19, 0)->startOfMinute();
        $setting = \App\Models\Setting::query()->where('name', 'advanced')->firstOrFail();
        $property = json_decode($setting->property, true);
        $dayOfWeek = (int) $slot->format('N');

        $property['week_set'][$dayOfWeek] = [
            $slot->format('H:i') => [2],
        ];
        $property['day_off'] = [];
        $property['max_asporto'] = 4;
        $property['max_domicilio'] = 4;

        $setting->update([
            'property' => json_encode($property),
        ]);

        return $slot;
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'name' => 'Promo',
            'surname' => 'Customer',
            'email' => $email,
            'phone' => '3331112222',
        ]);
    }

    private function createCategory(): int
    {
        $categoryId = $this->insertRow('categories', [
            'name' => 'Categoria test',
            'icon' => null,
        ]);

        if (Schema::hasTable('category_translations')) {
            $this->insertRow('category_translations', [
                'category_id' => $categoryId,
                'lang' => 'it',
                'name' => 'Categoria test',
                'description' => null,
            ]);
        }

        return $categoryId;
    }

    private function createProduct(int $categoryId, float $price): int
    {
        $productId = $this->insertRow('products', [
            'category_id' => $categoryId,
            'name' => 'Prodotto test',
            'price' => $price,
            'old_price' => null,
            'image' => null,
            'description' => null,
            'allergens' => null,
            'slot_plate' => null,
            'type_plate' => null,
            'tag_set' => null,
            'visible' => true,
            'archived' => false,
            'promotion' => false,
        ]);

        if (Schema::hasTable('product_translations')) {
            $this->insertRow('product_translations', [
                'product_id' => $productId,
                'lang' => 'it',
                'name' => 'Prodotto test',
                'description' => null,
            ]);
        }

        return $productId;
    }

    private function createPromotion(array $attributes, array $targets): Promotion
    {
        $promotion = Promotion::query()->create(array_merge([
            'name' => 'Promo ordine',
            'slug' => 'promo-order-' . Str::uuid(),
            'status' => 'active',
            'case_use' => 'take_away',
            'discount' => null,
            'type_discount' => 'fixed',
            'minimum_pretest' => null,
            'permanent' => true,
        ], $attributes));

        foreach ($targets as $target) {
            PromotionTarget::query()->create([
                'promotion_id' => $promotion->id,
                'target_type' => $target['type'],
                'target_id' => $target['id'],
            ]);
        }

        return $promotion->load('targets');
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

    private function setOrderMinimum(string $settingName, float $minimum): void
    {
        $setting = \App\Models\Setting::query()->where('name', $settingName)->firstOrFail();
        $property = json_decode($setting->property, true) ?: [];
        $property['min_price'] = $minimum;

        $setting->update([
            'property' => json_encode($property),
        ]);
    }

    private function cart(array $products = [], array $menus = []): array
    {
        return [
            'products' => $products,
            'menus' => $menus,
        ];
    }

    private function cartProduct(int $productId, int $quantity = 1): array
    {
        return [
            'id' => $productId,
            'counter' => $quantity,
            'remove' => [],
            'add' => [],
            'option' => [],
        ];
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
