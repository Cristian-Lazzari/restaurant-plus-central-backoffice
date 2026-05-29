<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Promotion;
use App\Models\PromotionTarget;
use App\Services\Marketing\OrderPromotionApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderPromotionApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_fixed_discount_applies_only_to_target_product(): void
    {
        $customer = $this->createCustomer('product-fixed@example.com');
        $categoryId = $this->createCategory();
        $targetProductId = $this->createProduct($categoryId, 10);
        $otherProductId = $this->createProduct($categoryId, 8);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $targetProductId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($targetProductId, 2),
            $this->cartProduct($otherProductId),
        ]), $customerPromotion->id);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(28.0, $result['subtotal']);
        $this->assertEquals(5.0, $result['discount_amount']);
        $this->assertEquals(23.0, $result['total']);
        $this->assertCount(1, $result['affected_items']);
        $this->assertSame($targetProductId, $result['affected_items'][0]['id']);
        $this->assertEquals(10.0, $result['affected_items'][0]['applicable_amount']);
        $this->assertSame(1, $result['affected_items'][0]['discounted_quantity']);
    }

    public function test_category_percentage_discount_applies_to_products_in_category(): void
    {
        $customer = $this->createCustomer('category-percentage@example.com');
        $targetCategoryId = $this->createCategory();
        $otherCategoryId = $this->createCategory();
        $targetProductId = $this->createProduct($targetCategoryId, 20);
        $otherProductId = $this->createProduct($otherCategoryId, 10);
        $promotion = $this->createPromotion([
            'type_discount' => 'percentage',
            'discount' => 10,
        ], [
            ['type' => PromotionTarget::TYPE_CATEGORY, 'id' => $targetCategoryId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($targetProductId),
            $this->cartProduct($otherProductId),
        ]), $customerPromotion->id);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(30.0, $result['subtotal']);
        $this->assertEquals(2.0, $result['discount_amount']);
        $this->assertEquals(28.0, $result['total']);
        $this->assertCount(1, $result['affected_items']);
        $this->assertSame($targetProductId, $result['affected_items'][0]['id']);
    }

    public function test_category_percentage_discount_applies_to_one_matching_unit_only(): void
    {
        $customer = $this->createCustomer('category-single-unit@example.com');
        $targetCategoryId = $this->createCategory();
        $otherCategoryId = $this->createCategory();
        $expensiveProductId = $this->createProduct($targetCategoryId, 20);
        $otherTargetProductId = $this->createProduct($targetCategoryId, 15);
        $otherProductId = $this->createProduct($otherCategoryId, 10);
        $promotion = $this->createPromotion([
            'type_discount' => 'percentage',
            'discount' => 10,
        ], [
            ['type' => PromotionTarget::TYPE_CATEGORY, 'id' => $targetCategoryId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($expensiveProductId, 2),
            $this->cartProduct($otherTargetProductId),
            $this->cartProduct($otherProductId),
        ]), $customerPromotion->id);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(65.0, $result['subtotal']);
        $this->assertEquals(2.0, $result['discount_amount']);
        $this->assertEquals(63.0, $result['total']);
        $this->assertCount(1, $result['affected_items']);
        $this->assertSame($expensiveProductId, $result['affected_items'][0]['id']);
        $this->assertEquals(20.0, $result['affected_items'][0]['applicable_amount']);
        $this->assertEquals(40.0, $result['affected_items'][0]['line_total']);
        $this->assertSame(1, $result['affected_items'][0]['discounted_quantity']);
    }

    public function test_menu_fixed_discount_applies_to_target_menu(): void
    {
        $customer = $this->createCustomer('menu-fixed@example.com');
        $categoryId = $this->createCategory();
        $targetMenuId = $this->createMenu($categoryId, 18);
        $otherMenuId = $this->createMenu($categoryId, 12);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 6,
        ], [
            ['type' => PromotionTarget::TYPE_MENU, 'id' => $targetMenuId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([], [
            $this->cartMenu($targetMenuId),
            $this->cartMenu($otherMenuId),
        ]), $customerPromotion->id);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(30.0, $result['subtotal']);
        $this->assertEquals(6.0, $result['discount_amount']);
        $this->assertEquals(24.0, $result['total']);
        $this->assertCount(1, $result['affected_items']);
        $this->assertSame($targetMenuId, $result['affected_items'][0]['id']);
    }

    public function test_table_promotion_is_not_applicable_to_order(): void
    {
        $customer = $this->createCustomer('table-case@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 10);
        $promotion = $this->createPromotion([
            'case_use' => 'table',
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_GENERIC, 'id' => null],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ]), $customerPromotion->id);

        $this->assertFalse($result['applicable']);
        $this->assertSame('invalid_case_use', $result['reason']);
    }

    public function test_minimum_pretest_blocks_when_subtotal_is_not_enough(): void
    {
        $customer = $this->createCustomer('minimum@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 10);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
            'minimum_pretest' => 20,
        ], [
            ['type' => PromotionTarget::TYPE_GENERIC, 'id' => null],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ]), $customerPromotion->id);

        $this->assertFalse($result['applicable']);
        $this->assertSame('minimum_not_reached', $result['reason']);
        $this->assertEquals(20.0, $result['minimum_required']);
        $this->assertEquals(10.0, $result['subtotal']);
    }

    public function test_order_minimum_pretest_is_checked_against_discounted_total(): void
    {
        $customer = $this->createCustomer('discounted-minimum@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 22);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
            'minimum_pretest' => 20,
        ], [
            ['type' => PromotionTarget::TYPE_GENERIC, 'id' => null],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ]), $customerPromotion->id);

        $this->assertFalse($result['applicable']);
        $this->assertSame('minimum_not_reached', $result['reason']);
        $this->assertEquals(22.0, $result['subtotal']);
        $this->assertEquals(22.0, $result['total']);
        $this->assertEquals(20.0, $result['minimum_required']);
        $this->assertEquals(17.0, $result['minimum_checked_total']);
        $this->assertEquals(3.0, $result['minimum_missing']);
    }

    public function test_order_minimum_pretest_allows_discounted_total_that_reaches_minimum(): void
    {
        $customer = $this->createCustomer('discounted-minimum-ok@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 25);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
            'minimum_pretest' => 20,
        ], [
            ['type' => PromotionTarget::TYPE_GENERIC, 'id' => null],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ]), $customerPromotion->id);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(25.0, $result['subtotal']);
        $this->assertEquals(5.0, $result['discount_amount']);
        $this->assertEquals(20.0, $result['total']);
        $this->assertEquals(0.0, $result['minimum_missing']);
    }

    public function test_recurring_day_and_time_limits_block_order_promotion(): void
    {
        $customer = $this->createCustomer('order-availability@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 10);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
            'valid_weekdays' => [1],
            'valid_from_time' => '07:00',
            'valid_to_time' => '08:00',
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ], [], [
            'date_slot' => '2026-05-11 09:00',
        ]), $customerPromotion->id);

        $this->assertFalse($result['applicable']);
        $this->assertSame('promotion_not_available_at_time', $result['reason']);
    }

    public function test_gift_product_discounts_one_target_unit(): void
    {
        $customer = $this->createCustomer('gift-product@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 12);
        $promotion = $this->createPromotion([
            'type_discount' => 'gift',
            'discount' => null,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId, 2),
        ]), $customerPromotion->id);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(24.0, $result['subtotal']);
        $this->assertEquals(12.0, $result['discount_amount']);
        $this->assertEquals(12.0, $result['total']);
        $this->assertSame(1, $result['affected_items'][0]['gift_quantity']);
    }

    public function test_customer_promotion_from_another_customer_is_not_applicable(): void
    {
        $customer = $this->createCustomer('right-customer@example.com');
        $otherCustomer = $this->createCustomer('wrong-customer@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 10);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($otherCustomer, $promotion);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ]), $customerPromotion->id);

        $this->assertFalse($result['applicable']);
        $this->assertSame('customer_promotion_customer_mismatch', $result['reason']);
    }

    public function test_used_customer_promotion_is_not_applicable(): void
    {
        $customer = $this->createCustomer('used-promo@example.com');
        $categoryId = $this->createCategory();
        $productId = $this->createProduct($categoryId, 10);
        $promotion = $this->createPromotion([
            'type_discount' => 'fixed',
            'discount' => 5,
        ], [
            ['type' => PromotionTarget::TYPE_PRODUCT, 'id' => $productId],
        ]);
        $customerPromotion = $this->assignPromotion($customer, $promotion, [
            'status' => 'used',
            'promo_used' => now(),
        ]);

        $result = $this->service()->evaluate($customer, $this->cart([
            $this->cartProduct($productId),
        ]), $customerPromotion->id);

        $this->assertFalse($result['applicable']);
        $this->assertSame('customer_promotion_already_used', $result['reason']);
    }

    private function service(): OrderPromotionApplicationService
    {
        return app(OrderPromotionApplicationService::class);
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
        $attributes = ['icon' => null];

        if (Schema::hasColumn('categories', 'name')) {
            $attributes['name'] = 'Category';
        }

        if (Schema::hasColumn('categories', 'description')) {
            $attributes['description'] = null;
        }

        return $this->insertRow('categories', $attributes);
    }

    private function createProduct(int $categoryId, float $price): int
    {
        $attributes = [
            'category_id' => $categoryId,
            'price' => $price,
            'old_price' => null,
            'image' => null,
            'allergens' => null,
            'slot_plate' => null,
            'type_plate' => null,
            'tag_set' => null,
            'visible' => true,
            'archived' => false,
            'promotion' => false,
        ];

        if (Schema::hasColumn('products', 'name')) {
            $attributes['name'] = 'Product';
        }

        if (Schema::hasColumn('products', 'description')) {
            $attributes['description'] = null;
        }

        return $this->insertRow('products', $attributes);
    }

    private function createMenu(int $categoryId, float $price, string $fixedMenu = '0'): int
    {
        $attributes = [
            'category_id' => $categoryId,
            'image' => null,
            'price' => $price,
            'old_price' => 0,
            'fixed_menu' => $fixedMenu,
            'visible' => true,
            'promo' => false,
        ];

        if (Schema::hasColumn('menus', 'name')) {
            $attributes['name'] = 'Menu';
        }

        if (Schema::hasColumn('menus', 'description')) {
            $attributes['description'] = null;
        }

        return $this->insertRow('menus', $attributes);
    }

    private function createPromotion(array $attributes, array $targets): Promotion
    {
        $promotion = Promotion::query()->create(array_merge([
            'name' => 'Promotion test',
            'slug' => 'promotion-' . Str::uuid(),
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

    private function cart(array $products = [], array $menus = [], array $attributes = []): array
    {
        return array_merge([
            'products' => $products,
            'menus' => $menus,
        ], $attributes);
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

    private function cartMenu(int $menuId, int $quantity = 1, array $products = []): array
    {
        return [
            'id' => $menuId,
            'counter' => $quantity,
            'combo_menu' => $products !== [],
            'products' => $products,
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
