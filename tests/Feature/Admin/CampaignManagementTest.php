<?php

namespace Tests\Feature\Admin;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Marketing\CampaignAssignmentService;
use App\Services\Marketing\CampaignAudienceBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_campaign_creation_redirects_to_index(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.campaigns.store'), [
            'name' => 'Campagna da finire',
            'submit_action' => 'draft',
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'segment' => 'all',
            'schedule_window' => 'next_available',
            'promotions' => [],
        ]);

        $response->assertRedirect(route('admin.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campagna da finire',
            'status' => 'draft',
        ]);
    }

    public function test_campaign_creation_with_soft_marketing_forces_soft_consent_and_accepts_base_segment(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.campaigns.store'), $this->campaignPayload([
            'name' => 'Campagna soft marketing',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_SOFT_MARKETING,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'reservations',
        ]));

        $response->assertRedirect(route('admin.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campagna soft marketing',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_SOFT_MARKETING,
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
            'segment' => 'reservations',
        ]);
    }

    public function test_campaign_creation_with_explicit_email_marketing_forces_explicit_consent_and_accepts_base_segment(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.campaigns.store'), $this->campaignPayload([
            'name' => 'Campagna consenso esplicito',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'segment' => 'orders',
        ]));

        $response->assertRedirect(route('admin.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campagna consenso esplicito',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
            'segment' => 'orders',
        ]);
    }

    public function test_campaign_creation_with_profiling_forces_explicit_consent_and_accepts_advanced_segment(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.campaigns.store'), $this->campaignPayload([
            'name' => 'Campagna profilazione',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_PROFILING,
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'segment' => 'new_customers',
        ]));

        $response->assertRedirect(route('admin.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campagna profilazione',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_PROFILING,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
            'segment' => 'new_customers',
        ]);
    }

    public function test_campaign_segment_validation_depends_on_campaign_type(): void
    {
        $admin = User::factory()->create();

        $cases = [
            [Campaign::CAMPAIGN_TYPE_SOFT_MARKETING, 'high_value_customers'],
            [Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING, 'high_value_customers'],
            [Campaign::CAMPAIGN_TYPE_PROFILING, 'reservations'],
            [Campaign::CAMPAIGN_TYPE_PROFILING, 'orders'],
            [Campaign::CAMPAIGN_TYPE_PROFILING, 'both'],
        ];

        foreach ($cases as $index => [$campaignType, $segment]) {
            $name = sprintf('Campagna segmento non valido %d', $index);

            $response = $this->actingAs($admin)->post(route('admin.campaigns.store'), $this->campaignPayload([
                'name' => $name,
                'campaign_type' => $campaignType,
                'segment' => $segment,
            ]));

            $response->assertSessionHasErrors('segment');
            $this->assertDatabaseMissing('campaigns', ['name' => $name]);
        }
    }

    public function test_base_campaign_segments_build_expected_audiences(): void
    {
        $orderOnly = $this->createAudienceCustomer('campaign-order-only@example.com');
        $reservationOnly = $this->createAudienceCustomer('campaign-reservation-only@example.com');
        $bothChannels = $this->createAudienceCustomer('campaign-both@example.com');
        $withoutActivity = $this->createAudienceCustomer('campaign-no-activity@example.com');

        $this->createOrderForCustomer($orderOnly, ['date' => now()->subDays(5)]);
        $this->createReservationForCustomer($reservationOnly, ['date' => now()->subDays(4)]);
        $this->createOrderForCustomer($bothChannels, ['date' => now()->subDays(3)]);
        $this->createReservationForCustomer($bothChannels, ['date' => now()->subDays(2)]);

        $ordersAudience = $this->audienceIdsForCampaign($this->createCampaign([
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'orders',
        ]));
        $reservationsAudience = $this->audienceIdsForCampaign($this->createCampaign([
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'reservations',
        ]));
        $bothAudience = $this->audienceIdsForCampaign($this->createCampaign([
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'both',
        ]));

        $this->assertContains($orderOnly->id, $ordersAudience);
        $this->assertContains($bothChannels->id, $ordersAudience);
        $this->assertNotContains($reservationOnly->id, $ordersAudience);
        $this->assertNotContains($withoutActivity->id, $ordersAudience);

        $this->assertContains($reservationOnly->id, $reservationsAudience);
        $this->assertContains($bothChannels->id, $reservationsAudience);
        $this->assertNotContains($orderOnly->id, $reservationsAudience);
        $this->assertNotContains($withoutActivity->id, $reservationsAudience);

        $this->assertContains($bothChannels->id, $bothAudience);
        $this->assertNotContains($orderOnly->id, $bothAudience);
        $this->assertNotContains($reservationOnly->id, $bothAudience);
        $this->assertNotContains($withoutActivity->id, $bothAudience);
    }

    public function test_profiling_campaign_audience_requires_profiling_consent_for_advanced_segments(): void
    {
        $withProfiling = $this->createAudienceCustomer('campaign-profiling-yes@example.com', [
            'profiling_consent_at' => now(),
        ]);
        $withoutProfiling = $this->createAudienceCustomer('campaign-profiling-no@example.com', [
            'profiling_consent_at' => null,
        ]);
        $withoutEmailConsent = $this->createAudienceCustomer('campaign-profiling-no-email@example.com', [
            'email_marketing_consent_at' => null,
            'profiling_consent_at' => now(),
        ]);

        $this->createOrderForCustomer($withProfiling, ['date' => now()->subDays(3)]);
        $this->createOrderForCustomer($withoutProfiling, ['date' => now()->subDays(3)]);
        $this->createOrderForCustomer($withoutEmailConsent, ['date' => now()->subDays(3)]);

        $audience = $this->audienceIdsForCampaign($this->createCampaign([
            'campaign_type' => Campaign::CAMPAIGN_TYPE_PROFILING,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'new_customers',
        ]));

        $this->assertContains($withProfiling->id, $audience);
        $this->assertNotContains($withoutProfiling->id, $audience);
        $this->assertNotContains($withoutEmailConsent->id, $audience);
    }

    public function test_campaign_audience_preview_uses_assignment_logic_for_profiling_segments(): void
    {
        $admin = User::factory()->create();
        $promotion = $this->createPromotion();
        $matched = $this->createAudienceCustomer('campaign-preview-profiling-match@example.com', [
            'profiling_consent_at' => now(),
        ]);
        $availableOutsideSegment = $this->createAudienceCustomer('campaign-preview-profiling-available@example.com', [
            'profiling_consent_at' => now(),
        ]);
        $withoutProfiling = $this->createAudienceCustomer('campaign-preview-profiling-missing@example.com', [
            'profiling_consent_at' => null,
        ]);
        $withoutEmailConsent = $this->createAudienceCustomer('campaign-preview-profiling-no-email@example.com', [
            'email_marketing_consent_at' => null,
            'profiling_consent_at' => now(),
        ]);

        $this->createOrderForCustomer($matched, ['date' => now()->subDays(3)]);
        $this->createOrderForCustomer($withoutProfiling, ['date' => now()->subDays(3)]);
        $this->createOrderForCustomer($withoutEmailConsent, ['date' => now()->subDays(3)]);

        $response = $this->actingAs($admin)->getJson(route('admin.campaigns.audience-preview', [
            'campaign_type' => Campaign::CAMPAIGN_TYPE_PROFILING,
            'segment' => 'new_customers',
            'promotions' => [$promotion->id],
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('matched', 1)
            ->assertJsonPath('available', 2)
            ->assertJsonPath('campaign_type', Campaign::CAMPAIGN_TYPE_PROFILING)
            ->assertJsonPath('consent_basis', Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING)
            ->assertJsonPath('segment', 'new_customers');

        $campaign = $this->createCampaign([
            'campaign_type' => Campaign::CAMPAIGN_TYPE_PROFILING,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'new_customers',
        ]);
        $campaign->promotions()->attach($promotion);
        $assignmentPreview = app(CampaignAssignmentService::class)->assign($campaign, 500, true);

        $this->assertSame($assignmentPreview['assigned_count'], $response->json('matched'));
        $this->assertFalse(in_array($availableOutsideSegment->id, $this->audienceIdsForCampaign($campaign), true));
    }

    public function test_campaign_audience_preview_handles_soft_and_explicit_email_rules(): void
    {
        $admin = User::factory()->create();
        $promotion = $this->createPromotion();
        $softOnly = $this->createAudienceCustomer('campaign-preview-soft-only@example.com', [
            'email_marketing_consent_at' => null,
            'marketing_consent_at' => null,
        ]);
        $explicit = $this->createAudienceCustomer('campaign-preview-explicit@example.com');
        $optedOut = $this->createAudienceCustomer('campaign-preview-soft-optout@example.com', [
            'email_marketing_consent_at' => null,
            'marketing_consent_at' => null,
            'soft_email_marketing_unsubscribed_at' => now(),
        ]);

        $this->createOrderForCustomer($softOnly, ['date' => now()->subDays(2)]);
        $this->createOrderForCustomer($explicit, ['date' => now()->subDays(2)]);
        $this->createOrderForCustomer($optedOut, ['date' => now()->subDays(2)]);

        $softResponse = $this->actingAs($admin)->getJson(route('admin.campaigns.audience-preview', [
            'campaign_type' => Campaign::CAMPAIGN_TYPE_SOFT_MARKETING,
            'segment' => 'orders',
            'promotions' => [$promotion->id],
        ]));

        $softResponse
            ->assertOk()
            ->assertJsonPath('matched', 2)
            ->assertJsonPath('available', 2)
            ->assertJsonPath('consent_basis', Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING);

        $explicitResponse = $this->actingAs($admin)->getJson(route('admin.campaigns.audience-preview', [
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'orders',
            'promotions' => [$promotion->id],
        ]));

        $explicitResponse
            ->assertOk()
            ->assertJsonPath('matched', 1)
            ->assertJsonPath('available', 1)
            ->assertJsonPath('consent_basis', Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING);
    }

    public function test_draft_campaign_can_be_deleted_with_connections(): void
    {
        $admin = User::factory()->create();
        $campaign = $this->createCampaign(['status' => 'draft']);
        $promotion = $this->createPromotion();
        $customer = $this->createCustomer();

        $campaign->promotions()->attach($promotion);
        CustomerPromotion::query()->create([
            'customer_id' => $customer->id,
            'promotion_id' => $promotion->id,
            'campaign_id' => $campaign->id,
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.campaigns.destroy', $campaign));

        $response->assertRedirect(route('admin.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertFalse(DB::table('campaign_promotion')->where('campaign_id', $campaign->id)->exists());
        $this->assertFalse(DB::table('customer_promotion')->where('campaign_id', $campaign->id)->exists());
    }

    public function test_campaign_index_renders_send_rate_and_draft_actions(): void
    {
        $admin = User::factory()->create();
        $promotion = $this->createPromotion(['slug' => 'promo-index-test']);
        $activeCampaign = $this->createCampaign([
            'name' => 'Campagna invii',
            'status' => 'running',
            'scheduled_at' => now(),
        ]);
        $draftCampaign = $this->createCampaign([
            'name' => 'Bozza da completare',
            'status' => 'draft',
        ]);

        $activeCampaign->promotions()->attach($promotion);
        $draftCampaign->promotions()->attach($promotion);

        for ($index = 1; $index <= 5; $index++) {
            CustomerPromotion::query()->create([
                'customer_id' => $this->createCustomer($index)->id,
                'promotion_id' => $promotion->id,
                'campaign_id' => $activeCampaign->id,
                'email_sent_at' => $index <= 2 ? now() : null,
                'status' => $index <= 2 ? 'sent' : 'assigned',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.campaigns.index'));

        $response->assertOk();
        $response->assertSee('40%');
        $response->assertSee('promo-index-test');
        $response->assertSee('Completa');
        $response->assertSee('Elimina');
        $response->assertSee('Apri');
        $response->assertSee('Archivia');
    }

    private function createCampaign(array $attributes = []): Campaign
    {
        return Campaign::query()->create(array_merge([
            'name' => 'Campagna test',
            'status' => 'draft',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'all',
        ], $attributes));
    }

    private function campaignPayload(array $attributes = []): array
    {
        return array_merge([
            'name' => 'Campagna test',
            'submit_action' => 'draft',
            'campaign_type' => Campaign::CAMPAIGN_TYPE_EXPLICIT_EMAIL_MARKETING,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'orders',
            'schedule_window' => 'next_available',
            'promotions' => [],
        ], $attributes);
    }

    private function createPromotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Promo test',
            'slug' => 'promo-test-' . uniqid(),
            'status' => 'active',
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

    private function createCustomer(int $index = 1): Customer
    {
        return Customer::query()->create([
            'name' => 'Cliente',
            'surname' => (string) $index,
            'email' => 'campaign-customer-' . $index . '-' . uniqid() . '@example.com',
        ]);
    }

    private function createAudienceCustomer(string $email, array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Audience',
            'surname' => 'Customer',
            'email' => $email,
            'phone' => '333'.random_int(1000000, 9999999),
            'registered_at' => now()->subMonth(),
            'marketing_consent_at' => null,
            'email_marketing_consent_at' => now()->subDay(),
            'profiling_consent_at' => null,
            'consents_updated_at' => now()->subDay(),
        ], $attributes));
    }

    private function createOrderForCustomer(Customer $customer, array $overrides = []): int
    {
        $date = $overrides['date'] ?? now();
        unset($overrides['date']);

        return DB::table('orders')->insertGetId(array_merge([
            'customer_id' => $customer->id,
            'date_slot' => $this->formatActivityDate($date),
            'status' => 1,
            'name' => $customer->name,
            'surname' => $customer->surname,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'checkout_session_id' => null,
            'address' => null,
            'address_n' => null,
            'comune' => null,
            'whatsapp_message_id' => null,
            'tot_price' => 25,
            'message' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => $date,
            'updated_at' => $date,
        ], $overrides));
    }

    private function createReservationForCustomer(Customer $customer, array $overrides = []): int
    {
        $date = $overrides['date'] ?? now();
        unset($overrides['date']);

        return DB::table('reservations')->insertGetId(array_merge([
            'customer_id' => $customer->id,
            'date_slot' => $this->formatActivityDate($date),
            'status' => 1,
            'name' => $customer->name,
            'surname' => $customer->surname,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'n_person' => json_encode(['adult' => 2, 'child' => 0]),
            'sala' => null,
            'message' => null,
            'whatsapp_message_id' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => $date,
            'updated_at' => $date,
        ], $overrides));
    }

    private function audienceIdsForCampaign(Campaign $campaign): array
    {
        return app(CampaignAudienceBuilder::class)
            ->queryForCampaign($campaign)
            ->pluck('id')
            ->all();
    }

    private function formatActivityDate($date): string
    {
        return $date->format('d/m/Y H:i');
    }
}
