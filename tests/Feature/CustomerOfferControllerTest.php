<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Promotion;
use App\Models\PromotionTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerOfferControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_offers_returns_enriched_promotion_payload_and_all_targets(): void
    {
        $customer = $this->createCustomer('offers-enriched@example.com');
        $categoryId = $this->createCategory('Pizze speciali', 'public/uploads/category.jpg');
        $productId = $this->createProduct($categoryId, 'Margherita special', 12, 'public/uploads/product.jpg');
        $promotion = $this->createPromotion([
            'name' => 'Promo speciale',
            'type_discount' => 'percentage',
            'discount' => 15,
            'minimum_pretest' => 30,
            'case_use' => 'take_away',
            'permanent' => false,
            'schedule_at' => now()->subDay(),
            'expiring_at' => now()->addDays(5),
        ]);

        $this->createTarget($promotion, PromotionTarget::TYPE_PRODUCT, $productId);
        $this->createTarget($promotion, PromotionTarget::TYPE_CATEGORY, $categoryId);
        $this->createTarget($promotion, PromotionTarget::TYPE_GENERIC);
        $customerPromotion = $this->assignPromotion($customer, $promotion);

        $this->getJson('/api/auth/offers', $this->authHeaders($customer))
            ->assertOk()
            ->assertJsonPath('available.0.id', $customerPromotion->id)
            ->assertJsonPath('available.0.customer_promotion_id', $customerPromotion->id)
            ->assertJsonPath('available.0.promotion_id', $promotion->id)
            ->assertJsonPath('available.0.name', 'Promo speciale')
            ->assertJsonPath('available.0.title', 'Promo speciale')
            ->assertJsonPath('available.0.case_use', 'take_away')
            ->assertJsonPath('available.0.status', 'available')
            ->assertJsonPath('available.0.type_discount', 'percentage')
            ->assertJsonPath('available.0.discount', 15)
            ->assertJsonPath('available.0.minimum_pretest', 30)
            ->assertJsonPath('available.0.minimum_required', 30)
            ->assertJsonPath('available.0.permanent', false)
            ->assertJsonPath('available.0.target_type', PromotionTarget::TYPE_PRODUCT)
            ->assertJsonPath('available.0.target_id', $productId)
            ->assertJsonPath('available.0.target_image', 'public/uploads/product.jpg')
            ->assertJsonPath('available.0.image', 'public/uploads/product.jpg')
            ->assertJsonPath('available.0.product_name', 'Margherita special')
            ->assertJsonPath('available.0.product_image', 'public/uploads/product.jpg')
            ->assertJsonPath('available.0.category_name', null)
            ->assertJsonPath('available.0.targets.0.type', PromotionTarget::TYPE_PRODUCT)
            ->assertJsonPath('available.0.targets.0.id', $productId)
            ->assertJsonPath('available.0.targets.0.name', 'Margherita special')
            ->assertJsonPath('available.0.targets.0.image', 'public/uploads/product.jpg')
            ->assertJsonPath('available.0.targets.1.type', PromotionTarget::TYPE_CATEGORY)
            ->assertJsonPath('available.0.targets.1.id', $categoryId)
            ->assertJsonPath('available.0.targets.1.name', 'Pizze speciali')
            ->assertJsonPath('available.0.targets.1.image', 'public/uploads/category.jpg')
            ->assertJsonPath('available.0.targets.2.type', PromotionTarget::TYPE_GENERIC)
            ->assertJsonPath('available.0.targets.2.id', null)
            ->assertJsonPath('available.0.targets.2.name', 'Tutto l\'ordine')
            ->assertJsonPath('available.0.targets.2.image', null);
    }

    public function test_auth_offers_keeps_used_and_expired_out_of_available(): void
    {
        $customer = $this->createCustomer('offers-status@example.com');
        $categoryId = $this->createCategory('Promozioni');
        $productId = $this->createProduct($categoryId, 'Prodotto promo', 10);

        $availablePromotion = $this->createPromotion([
            'name' => 'Disponibile',
            'type_discount' => 'fixed',
            'discount' => 5,
        ]);
        $usedPromotion = $this->createPromotion([
            'name' => 'Gia usata',
            'type_discount' => 'fixed',
            'discount' => 5,
        ]);
        $expiredPromotion = $this->createPromotion([
            'name' => 'Scaduta',
            'type_discount' => 'fixed',
            'discount' => 5,
            'permanent' => false,
            'expiring_at' => now()->subDay(),
        ]);

        foreach ([$availablePromotion, $usedPromotion, $expiredPromotion] as $promotion) {
            $this->createTarget($promotion, PromotionTarget::TYPE_PRODUCT, $productId);
        }

        $available = $this->assignPromotion($customer, $availablePromotion);
        $used = $this->assignPromotion($customer, $usedPromotion, [
            'status' => 'used',
            'promo_used' => now(),
        ]);
        $expired = $this->assignPromotion($customer, $expiredPromotion);

        $response = $this->getJson('/api/auth/offers', $this->authHeaders($customer))
            ->assertOk();

        $this->assertSame([$available->id], array_column($response->json('available'), 'id'));
        $this->assertSame([$used->id], array_column($response->json('used'), 'id'));
        $this->assertSame([$expired->id], array_column($response->json('expired'), 'id'));
    }

    public function test_auth_offers_can_show_used_history_and_fresh_available_assignment_for_reusable_promotion(): void
    {
        $customer = $this->createCustomer('offers-reusable@example.com');
        $categoryId = $this->createCategory('Promozioni riutilizzabili');
        $productId = $this->createProduct($categoryId, 'Prodotto riutilizzabile', 10);
        $promotion = $this->createPromotion([
            'name' => 'Promo riutilizzabile',
            'type_discount' => 'fixed',
            'discount' => 5,
            'metadata' => [
                'reusable' => true,
            ],
        ]);

        $this->createTarget($promotion, PromotionTarget::TYPE_PRODUCT, $productId);

        $used = $this->assignPromotion($customer, $promotion, [
            'status' => 'used',
            'promo_used' => now(),
        ]);
        $available = $this->assignPromotion($customer, $promotion, [
            'metadata' => [
                'reusable_parent_id' => $used->id,
                'source' => 'reusable_promotion',
            ],
        ]);

        $response = $this->getJson('/api/auth/offers', $this->authHeaders($customer))
            ->assertOk();

        $this->assertSame([$available->id], array_column($response->json('available'), 'customer_promotion_id'));
        $this->assertSame([$used->id], array_column($response->json('used'), 'customer_promotion_id'));
        $this->assertSame($promotion->id, $response->json('available.0.promotion_id'));
        $this->assertSame($promotion->id, $response->json('used.0.promotion_id'));
    }

    private function authHeaders(Customer $customer): array
    {
        return [
            'Authorization' => 'Bearer ' . $customer->createToken('customer-api')->plainTextToken,
        ];
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'name' => 'Offer',
            'surname' => 'Customer',
            'email' => $email,
            'phone' => '3331112222',
        ]);
    }

    private function createCategory(string $name, ?string $icon = null): int
    {
        $categoryId = $this->insertRow('categories', [
            'name' => $name,
            'icon' => $icon,
        ]);

        $this->insertRow('category_translations', [
            'category_id' => $categoryId,
            'lang' => 'en',
            'name' => $name,
            'description' => null,
        ]);

        return $categoryId;
    }

    private function createProduct(int $categoryId, string $name, float $price, ?string $image = null): int
    {
        $productId = $this->insertRow('products', [
            'category_id' => $categoryId,
            'name' => $name,
            'price' => $price,
            'old_price' => null,
            'image' => $image,
            'description' => null,
            'allergens' => null,
            'slot_plate' => null,
            'type_plate' => null,
            'tag_set' => null,
            'visible' => true,
            'archived' => false,
            'promotion' => false,
        ]);

        $this->insertRow('product_translations', [
            'product_id' => $productId,
            'lang' => 'en',
            'name' => $name,
            'description' => null,
        ]);

        return $productId;
    }

    private function createPromotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Promo test',
            'slug' => 'promo-' . Str::uuid(),
            'status' => 'active',
            'case_use' => 'take_away',
            'type_discount' => 'fixed',
            'discount' => 5,
            'minimum_pretest' => null,
            'permanent' => true,
            'schedule_at' => null,
            'expiring_at' => null,
        ], $attributes));
    }

    private function createTarget(Promotion $promotion, string $type, ?int $id = null): void
    {
        PromotionTarget::query()->create([
            'promotion_id' => $promotion->id,
            'target_type' => $type,
            'target_id' => $id,
        ]);
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
