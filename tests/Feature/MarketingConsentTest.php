<?php

namespace Tests\Feature;

use App\Mail\MarketingPromotionMail;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerPromotion;
use App\Models\Promotion;
use App\Services\Marketing\CampaignAudienceBuilder;
use App\Services\Marketing\MarketingConsentService;
use App\Services\Marketing\MarketingCustomerSegmentService;
use App\Services\Marketing\MarketingEmailDispatchService;
use App\Services\Marketing\MarketingTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketingConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_marketing_audience_uses_explicit_consent_with_legacy_fallback(): void
    {
        $explicit = $this->createCustomer('explicit@example.com', [
            'email_marketing_consent_at' => now(),
        ]);
        $legacy = $this->createCustomer('legacy@example.com', [
            'marketing_consent_at' => now()->subDay(),
        ]);
        $revoked = $this->createCustomer('revoked@example.com', [
            'marketing_consent_at' => now()->subDay(),
            'consents_updated_at' => now(),
        ]);
        $withoutConsent = $this->createCustomer('no-consent@example.com');

        $customerIds = app(MarketingCustomerSegmentService::class)
            ->queryForSegment('all')
            ->pluck('id')
            ->all();

        $this->assertContains($explicit->id, $customerIds);
        $this->assertContains($legacy->id, $customerIds);
        $this->assertNotContains($revoked->id, $customerIds);
        $this->assertNotContains($withoutConsent->id, $customerIds);
    }

    public function test_marketing_email_dispatch_requires_email_marketing_consent_or_legacy_fallback(): void
    {
        $dispatchService = app(MarketingEmailDispatchService::class);

        $explicit = $this->createCustomerPromotion($this->createCustomer('send-explicit@example.com', [
            'email_marketing_consent_at' => now(),
        ]));
        $legacy = $this->createCustomerPromotion($this->createCustomer('send-legacy@example.com', [
            'marketing_consent_at' => now()->subDay(),
        ]));
        $revoked = $this->createCustomerPromotion($this->createCustomer('send-revoked@example.com', [
            'marketing_consent_at' => now()->subDay(),
            'consents_updated_at' => now(),
        ]));
        $withoutConsent = $this->createCustomerPromotion($this->createCustomer('send-no-consent@example.com'));

        $this->assertTrue($dispatchService->sendCustomerPromotion($explicit, true)['can_send']);
        $this->assertTrue($dispatchService->sendCustomerPromotion($legacy, true)['can_send']);
        $this->assertFalse($dispatchService->sendCustomerPromotion($revoked, true)['can_send']);
        $this->assertFalse($dispatchService->sendCustomerPromotion($withoutConsent, true)['can_send']);
    }

    public function test_explicit_email_marketing_campaign_audience_uses_explicit_consent(): void
    {
        $campaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $explicit = $this->createCustomer('campaign-explicit@example.com', [
            'email_marketing_consent_at' => now(),
        ]);
        $legacy = $this->createCustomer('campaign-legacy@example.com', [
            'marketing_consent_at' => now()->subDay(),
        ]);
        $revoked = $this->createCustomer('campaign-revoked@example.com', [
            'marketing_consent_at' => now()->subDay(),
            'consents_updated_at' => now(),
        ]);
        $withoutConsent = $this->createCustomer('campaign-no-consent@example.com');

        $customerIds = app(CampaignAudienceBuilder::class)
            ->queryForCampaign($campaign)
            ->pluck('id')
            ->all();

        $this->assertContains($explicit->id, $customerIds);
        $this->assertContains($legacy->id, $customerIds);
        $this->assertNotContains($revoked->id, $customerIds);
        $this->assertNotContains($withoutConsent->id, $customerIds);
    }

    public function test_soft_email_marketing_campaign_audience_allows_privacy_only_customers_and_respects_soft_opt_out(): void
    {
        $softCampaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $explicitCampaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $privacyOnly = $this->createCustomer('soft-allowed@example.com', [
            'privacy_accepted_at' => now(),
            'email_marketing_consent_at' => null,
            'marketing_consent_at' => null,
            'tracking_consent_at' => null,
            'profiling_consent_at' => null,
            'soft_email_marketing_unsubscribed_at' => null,
        ]);
        $optedOut = $this->createCustomer('soft-opted-out@example.com', [
            'privacy_accepted_at' => now(),
            'email_marketing_consent_at' => null,
            'marketing_consent_at' => null,
            'soft_email_marketing_unsubscribed_at' => now(),
        ]);

        $softCustomerIds = app(CampaignAudienceBuilder::class)
            ->queryForCampaign($softCampaign)
            ->pluck('id')
            ->all();
        $explicitCustomerIds = app(CampaignAudienceBuilder::class)
            ->queryForCampaign($explicitCampaign)
            ->pluck('id')
            ->all();

        $this->assertContains($privacyOnly->id, $softCustomerIds);
        $this->assertNotContains($privacyOnly->id, $explicitCustomerIds);
        $this->assertNotContains($optedOut->id, $softCustomerIds);
    }

    public function test_marketing_email_dispatch_blocks_soft_email_campaign_when_customer_opted_out(): void
    {
        Mail::fake();

        $campaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $customerPromotion = $this->createCustomerPromotion($this->createCustomer('soft-send-opt-out@example.com', [
            'privacy_accepted_at' => now(),
            'soft_email_marketing_unsubscribed_at' => now(),
        ]), $campaign);

        $result = app(MarketingEmailDispatchService::class)
            ->sendCustomerPromotion($customerPromotion, false);

        $this->assertFalse($result['can_send']);
        $this->assertFalse($result['sent']);
        $this->assertSame('Customer soft email marketing opt-out is present.', $result['failure_reason']);
        Mail::assertNotSent(MarketingPromotionMail::class);
    }

    public function test_soft_email_marketing_availability_does_not_require_explicit_email_consent(): void
    {
        $consentService = app(MarketingConsentService::class);
        $privacyOnly = $this->createCustomer('privacy-only@example.com', [
            'privacy_accepted_at' => now(),
        ]);
        $optedOut = $this->createCustomer('soft-disabled@example.com', [
            'privacy_accepted_at' => now(),
            'soft_email_marketing_unsubscribed_at' => now(),
        ]);

        $this->assertFalse($consentService->customerHasExplicitEmailMarketingConsent($privacyOnly));
        $this->assertTrue($consentService->customerAllowsSoftEmailMarketing($privacyOnly));
        $this->assertFalse($consentService->customerAllowsSoftEmailMarketing($optedOut));
    }

    public function test_soft_email_marketing_count_includes_incomplete_privacy_only_customer(): void
    {
        $privacyOnly = $this->createCustomer('soft-incomplete@example.com', [
            'privacy_accepted_at' => now(),
            'email_marketing_consent_at' => null,
            'marketing_consent_at' => null,
            'soft_email_marketing_unsubscribed_at' => null,
        ]);

        $builder = app(CampaignAudienceBuilder::class);

        $this->assertNull($privacyOnly->gender);
        $this->assertNull($privacyOnly->birthday);
        $this->assertSame(1, $builder->countForSegmentAndConsentBasis('all', Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING));
        $this->assertSame(0, $builder->countForSegmentAndConsentBasis('all', Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING));
    }

    public function test_campaign_audience_availability_and_matrix_count_email_bases(): void
    {
        $this->createCustomer('matrix-soft-one@example.com', [
            'privacy_accepted_at' => now(),
        ]);
        $this->createCustomer('matrix-explicit@example.com', [
            'privacy_accepted_at' => now(),
            'email_marketing_consent_at' => now(),
        ]);
        $this->createCustomer('matrix-soft-two@example.com', [
            'privacy_accepted_at' => now(),
        ]);

        $builder = app(CampaignAudienceBuilder::class);
        $availability = $builder->availabilityByConsentBasis();
        $matrix = $builder->matrixForSegments(['all', 'loyal_customers']);

        $this->assertSame(3, $availability[Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING]['eligible']);
        $this->assertSame(3, $availability[Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING]['total']);
        $this->assertSame(1, $availability[Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING]['eligible']);
        $this->assertSame(3, $availability[Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING]['total']);
        $this->assertSame(3, $matrix[Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING]['all']);
        $this->assertSame(1, $matrix[Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING]['all']);
    }

    public function test_whatsapp_marketing_campaign_audience_uses_whatsapp_consent(): void
    {
        $campaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_WHATSAPP_MARKETING,
            'channel' => Campaign::CHANNEL_WHATSAPP,
        ]);
        $whatsappConsent = $this->createCustomer('whatsapp-consent@example.com', [
            'whatsapp_marketing_consent_at' => now(),
        ]);
        $emailOnly = $this->createCustomer('email-only@example.com', [
            'email_marketing_consent_at' => now(),
        ]);

        $customerIds = app(CampaignAudienceBuilder::class)
            ->queryForCampaign($campaign)
            ->pluck('id')
            ->all();

        $this->assertContains($whatsappConsent->id, $customerIds);
        $this->assertNotContains($emailOnly->id, $customerIds);
    }

    public function test_marketing_email_dispatch_blocks_whatsapp_campaigns(): void
    {
        Mail::fake();

        $campaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_WHATSAPP_MARKETING,
            'channel' => Campaign::CHANNEL_WHATSAPP,
        ]);
        $customerPromotion = $this->createCustomerPromotion($this->createCustomer('whatsapp-send@example.com', [
            'whatsapp_marketing_consent_at' => now(),
        ]), $campaign);

        $result = app(MarketingEmailDispatchService::class)
            ->sendCustomerPromotion($customerPromotion, false);

        $this->assertFalse($result['can_send']);
        $this->assertFalse($result['sent']);
        $this->assertSame('Canale WhatsApp non ancora implementato', $result['failure_reason']);
        Mail::assertNotSent(MarketingPromotionMail::class);
    }

    public function test_unsubscribe_explicit_email_marketing_revokes_explicit_email_consent_only(): void
    {
        $legacyConsentAt = now()->subDays(3)->startOfSecond();
        $customer = $this->createCustomer('unsubscribe-explicit@example.com', [
            'marketing_consent_at' => $legacyConsentAt,
            'email_marketing_consent_at' => now()->subDay(),
        ]);
        $campaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $customerPromotion = $this->createCustomerPromotion($customer, $campaign);

        $this->get('/api/marketing/unsubscribe/' . $customerPromotion->tracking_token)
            ->assertOk()
            ->assertSee('Iscrizione annullata correttamente.');

        $customer->refresh();
        $customerPromotion->refresh();

        $this->assertNull($customer->email_marketing_consent_at);
        $this->assertNull($customer->soft_email_marketing_unsubscribed_at);
        $this->assertNotNull($customer->consents_updated_at);
        $this->assertSame($legacyConsentAt->toDateTimeString(), $customer->marketing_consent_at->toDateTimeString());
        $this->assertNull($customerPromotion->email_open_at);
        $this->assertNull($customerPromotion->email_click_at);
    }

    public function test_unsubscribe_soft_email_marketing_sets_soft_opt_out_only(): void
    {
        $explicitConsentAt = now()->subDay()->startOfSecond();
        $customer = $this->createCustomer('unsubscribe-soft@example.com', [
            'email_marketing_consent_at' => $explicitConsentAt,
        ]);
        $campaign = $this->createCampaign([
            'consent_basis' => Campaign::CONSENT_BASIS_SOFT_EMAIL_MARKETING,
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $customerPromotion = $this->createCustomerPromotion($customer, $campaign);

        $this->get('/api/marketing/unsubscribe/' . $customerPromotion->tracking_token)
            ->assertOk()
            ->assertSee('Iscrizione annullata correttamente.');

        $customer->refresh();

        $this->assertSame($explicitConsentAt->toDateTimeString(), $customer->email_marketing_consent_at->toDateTimeString());
        $this->assertNotNull($customer->soft_email_marketing_unsubscribed_at);
        $this->assertNotNull($customer->consents_updated_at);
    }

    public function test_unsubscribe_unknown_campaign_basis_uses_safe_fallback(): void
    {
        $customer = $this->createCustomer('unsubscribe-fallback@example.com', [
            'email_marketing_consent_at' => now()->subDay(),
        ]);
        $campaign = $this->createCampaign([
            'consent_basis' => 'unknown_basis',
            'channel' => Campaign::CHANNEL_EMAIL,
        ]);
        $customerPromotion = $this->createCustomerPromotion($customer, $campaign);

        $this->get('/api/marketing/unsubscribe/' . $customerPromotion->tracking_token)
            ->assertOk()
            ->assertSee('Iscrizione annullata correttamente.');

        $customer->refresh();

        $this->assertNull($customer->email_marketing_consent_at);
        $this->assertNotNull($customer->soft_email_marketing_unsubscribed_at);
        $this->assertNotNull($customer->consents_updated_at);
    }

    public function test_unsubscribe_invalid_token_returns_generic_response(): void
    {
        $this->get('/api/marketing/unsubscribe/not-a-real-token')
            ->assertOk()
            ->assertSee('Richiesta gestita.');
    }

    public function test_marketing_renderer_builds_unsubscribe_url_from_app_url(): void
    {
        config([
            'app.url' => 'https://backend.test',
            'configurazione.domain' => 'https://public.test',
        ]);

        $customerPromotion = $this->createCustomerPromotion($this->createCustomer('unsubscribe-url@example.com', [
            'email_marketing_consent_at' => now(),
        ]));

        $rendered = app(MarketingTemplateRenderer::class)->render($customerPromotion);

        $this->assertSame(
            'https://backend.test/api/marketing/unsubscribe/' . $customerPromotion->tracking_token,
            $rendered['unsubscribe_url']
        );
        $this->assertSame('Annulla iscrizione', $rendered['unsubscribe_label']);
    }

    public function test_open_and_click_tracking_are_not_saved_without_tracking_consent(): void
    {
        config(['configurazione.domain' => 'https://restaurant.test']);

        $customerPromotion = $this->createCustomerPromotion($this->createCustomer('no-tracking@example.com', [
            'email_marketing_consent_at' => now(),
        ]));

        $this->get('/api/marketing/open/' . $customerPromotion->tracking_token)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        $customerPromotion->refresh();
        $this->assertNull($customerPromotion->email_open_at);

        $this->get('/api/marketing/click/' . $customerPromotion->tracking_token . '?redirect=' . rawurlencode('/promo'))
            ->assertRedirect('https://restaurant.test/promo');

        $customerPromotion->refresh();
        $this->assertNull($customerPromotion->email_click_at);
    }

    public function test_open_and_click_tracking_are_saved_with_tracking_consent(): void
    {
        config(['configurazione.domain' => 'https://restaurant.test']);

        $customerPromotion = $this->createCustomerPromotion($this->createCustomer('tracking@example.com', [
            'email_marketing_consent_at' => now(),
            'tracking_consent_at' => now(),
        ]));

        $this->get('/api/marketing/open/' . $customerPromotion->tracking_token)
            ->assertOk();

        $customerPromotion->refresh();
        $this->assertNotNull($customerPromotion->email_open_at);

        $this->get('/api/marketing/click/' . $customerPromotion->tracking_token . '?redirect=' . rawurlencode('/promo'))
            ->assertRedirect('https://restaurant.test/promo');

        $customerPromotion->refresh();
        $this->assertNotNull($customerPromotion->email_click_at);
    }

    private function createCustomer(string $email, array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Marketing',
            'surname' => 'Customer',
            'email' => $email,
            'phone' => '3331112222',
        ], $attributes));
    }

    private function createCampaign(array $attributes = []): Campaign
    {
        return Campaign::query()->create(array_merge([
            'name' => 'Campaign test',
            'status' => 'draft',
            'channel' => Campaign::CHANNEL_EMAIL,
            'consent_basis' => Campaign::CONSENT_BASIS_EXPLICIT_EMAIL_MARKETING,
            'segment' => 'all',
        ], $attributes));
    }

    private function createCustomerPromotion(Customer $customer, ?Campaign $campaign = null): CustomerPromotion
    {
        $promotion = Promotion::query()->create([
            'name' => 'Promo test',
            'slug' => 'promo-' . $customer->id,
            'status' => 'active',
            'cta' => '/promo',
            'permanent' => true,
        ]);

        if ($campaign) {
            $campaign->promotions()->syncWithoutDetaching([$promotion->id]);
        }

        return CustomerPromotion::query()->create([
            'customer_id' => $customer->id,
            'promotion_id' => $promotion->id,
            'campaign_id' => $campaign?->id,
            'tracking_token' => '00000000-0000-4000-8000-' . str_pad((string) $customer->id, 12, '0', STR_PAD_LEFT),
            'status' => 'assigned',
        ]);
    }
}
