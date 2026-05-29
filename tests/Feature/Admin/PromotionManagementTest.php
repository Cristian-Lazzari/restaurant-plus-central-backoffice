<?php

namespace Tests\Feature\Admin;

use App\Models\Automation;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_promotion_creation_redirects_to_index(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.promotions.store'), [
            'name' => 'Promo da finire',
            'submit_action' => 'draft',
            'case_use' => 'generic',
            'type_discount' => 'percentage',
            'discount' => 10,
            'minimum_pretest' => null,
            'cta' => null,
            'permanent' => '1',
            'metadata' => [
                'reusable' => '0',
            ],
            'target_type' => 'generic',
        ]);

        $response->assertRedirect(route('admin.promotions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'name' => 'Promo da finire',
            'status' => 'draft',
        ]);
    }

    public function test_draft_promotion_can_be_deleted_with_connections(): void
    {
        $admin = User::factory()->create();
        $promotion = $this->createPromotion(['status' => 'draft']);
        $campaign = Campaign::query()->create([
            'name' => 'Campagna collegata',
            'status' => 'draft',
            'segment' => 'all',
        ]);
        $automation = Automation::query()->create([
            'name' => 'Automazione collegata',
            'trigger' => 'first_order_completed',
            'status' => 'draft',
        ]);

        $promotion->targets()->create([
            'target_type' => 'generic',
            'target_id' => null,
        ]);
        $promotion->campaigns()->attach($campaign);
        $promotion->automations()->attach($automation);

        $response = $this->actingAs($admin)->delete(route('admin.promotions.destroy', $promotion));

        $response->assertRedirect(route('admin.promotions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
        $this->assertDatabaseMissing('promotion_targets', ['promotion_id' => $promotion->id]);
        $this->assertFalse(DB::table('campaign_promotion')->where('promotion_id', $promotion->id)->exists());
        $this->assertFalse(DB::table('automation_promotion')->where('promotion_id', $promotion->id)->exists());
    }

    public function test_promotion_index_renders_usage_rate_and_draft_actions(): void
    {
        $admin = User::factory()->create();
        $activePromotion = $this->createPromotion([
            'name' => 'Promo usage',
            'status' => 'active',
            'metadata' => [
                'reusable' => true,
            ],
        ]);
        $this->createPromotion([
            'name' => 'Bozza da completare',
            'status' => 'draft',
        ]);

        for ($index = 1; $index <= 5; $index++) {
            $customer = Customer::query()->create([
                'name' => 'Cliente',
                'surname' => (string) $index,
                'email' => 'cliente-' . $index . '@example.com',
            ]);

            CustomerPromotion::query()->create([
                'customer_id' => $customer->id,
                'promotion_id' => $activePromotion->id,
                'promo_used' => $index <= 2 ? now() : null,
                'status' => $index <= 2 ? 'used' : 'assigned',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.promotions.index'));

        $response->assertOk();
        $response->assertSee('40%');
        $response->assertSee('RIUTILIZZABILE');
        $response->assertSee('Completa');
        $response->assertSee('Elimina');
        $response->assertSee('Apri');
        $response->assertSee('Archivia');
    }

    public function test_archived_promotions_have_a_dedicated_index(): void
    {
        $admin = User::factory()->create();
        $this->createPromotion([
            'name' => 'Promo operativa',
            'status' => 'active',
        ]);
        $this->createPromotion([
            'name' => 'Promo archiviata',
            'status' => 'archived',
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('admin.promotions.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Promo operativa');
        $indexResponse->assertDontSee('Promo archiviata');
        $indexResponse->assertSee('promotions/archived');

        $archivedResponse = $this->actingAs($admin)->get(route('admin.promotions.archived'));

        $archivedResponse->assertOk();
        $archivedResponse->assertSee('Promozioni archiviate');
        $archivedResponse->assertSee('Promo archiviata');
        $archivedResponse->assertDontSee('Promo operativa');
        $archivedResponse->assertSee('Lista promozioni');
    }

    public function test_non_draft_promotion_cannot_be_deleted_directly(): void
    {
        $admin = User::factory()->create();
        $promotion = $this->createPromotion(['status' => 'active']);

        $response = $this->actingAs($admin)->delete(route('admin.promotions.destroy', $promotion));

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'status' => 'active',
        ]);
    }

    private function createPromotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Promo test',
            'slug' => 'promo-test-' . uniqid(),
            'status' => 'draft',
            'case_use' => 'generic',
            'discount' => 10,
            'type_discount' => 'percentage',
            'minimum_pretest' => null,
            'cta' => null,
            'permanent' => true,
            'metadata' => [
                'reusable' => false,
            ],
        ], $attributes));
    }
}
