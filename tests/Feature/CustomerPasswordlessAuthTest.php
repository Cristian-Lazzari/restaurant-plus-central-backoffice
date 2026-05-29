<?php

namespace Tests\Feature;

use App\Mail\CustomerOtpMail;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerAuth\CustomerAccessService;
use App\Services\CustomerAuth\CustomerProfileSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerPasswordlessAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_otp_can_create_customer_access_and_return_token(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/send-otp', [
            'email' => 'anna@example.com',
            'lang' => 'it',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'customer_exists' => false,
            ]);

        $otpCode = null;

        Mail::assertSent(CustomerOtpMail::class, function (CustomerOtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->code;

            return true;
        });

        $this->assertNotNull($otpCode);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'anna@example.com',
            'code' => $otpCode,
            'intent' => 'account',
            'lang' => 'it',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('customer.email', 'anna@example.com')
            ->assertJsonPath('verified_session.email', 'anna@example.com');

        $this->assertNotEmpty($response->json('token'));

        $this->assertDatabaseHas('customers', [
            'email' => 'anna@example.com',
        ]);
    }

    public function test_send_otp_is_rate_limited_per_email(): void
    {
        Mail::fake();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/auth/send-otp', [
                'email' => 'limited@example.com',
                'lang' => 'it',
            ])->assertOk();
        }

        $this->postJson('/api/auth/send-otp', [
            'email' => 'limited@example.com',
            'lang' => 'it',
        ])->assertStatus(429);
    }

    public function test_checkout_send_otp_is_skipped_when_email_exists_in_legacy_orders(): void
    {
        Mail::fake();

        DB::table('orders')->insert([
            'date_slot' => '2026-04-08 20:00',
            'status' => 1,
            'name' => 'Anna',
            'surname' => 'Legacy',
            'email' => 'anna@example.com',
            'phone' => '3331234567',
            'checkout_session_id' => null,
            'address' => null,
            'address_n' => null,
            'comune' => null,
            'whatsapp_message_id' => null,
            'tot_price' => 25.00,
            'message' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'anna@example.com',
            'intent' => 'checkout',
            'lang' => 'it',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'otp_required' => false,
                'customer_exists' => false,
            ])
            ->assertJsonPath('verified_session.email', 'anna@example.com');

        Mail::assertNothingSent();
    }

    public function test_account_send_otp_still_requires_code_for_legacy_order_email(): void
    {
        Mail::fake();

        DB::table('orders')->insert([
            'date_slot' => '2026-04-08 20:00',
            'status' => 1,
            'name' => 'Anna',
            'surname' => 'Legacy',
            'email' => 'anna@example.com',
            'phone' => '3331234567',
            'checkout_session_id' => null,
            'address' => null,
            'address_n' => null,
            'comune' => null,
            'whatsapp_message_id' => null,
            'tot_price' => 25.00,
            'message' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/auth/send-otp', [
            'email' => 'anna@example.com',
            'intent' => 'account',
            'lang' => 'it',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'otp_required' => true,
                'customer_exists' => false,
            ]);

        Mail::assertSent(CustomerOtpMail::class);
    }

    public function test_legacy_orders_and_reservations_are_linked_when_customer_access_is_completed(): void
    {
        Mail::fake();

        DB::table('orders')->insert([
            'date_slot' => '2026-04-08 20:00',
            'status' => 1,
            'name' => 'Anna',
            'surname' => 'Legacy',
            'email' => 'anna@example.com',
            'phone' => '3331234567',
            'checkout_session_id' => null,
            'address' => null,
            'address_n' => null,
            'comune' => null,
            'whatsapp_message_id' => null,
            'tot_price' => 25.00,
            'message' => null,
            'news_letter' => true,
            'notificated' => false,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('reservations')->insert([
            'date_slot' => '08/04/2026 21:00',
            'status' => 1,
            'name' => 'Anna',
            'surname' => 'Legacy',
            'email' => 'anna@example.com',
            'phone' => '3331234567',
            'n_person' => json_encode(['adult' => 2, 'child' => 0]),
            'sala' => null,
            'message' => null,
            'whatsapp_message_id' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/auth/send-otp', [
            'email' => 'anna@example.com',
            'intent' => 'account',
            'lang' => 'it',
        ])->assertOk();

        $otpCode = null;
        Mail::assertSent(CustomerOtpMail::class, function (CustomerOtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->code;

            return true;
        });

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'anna@example.com',
            'code' => $otpCode,
            'intent' => 'account',
            'lang' => 'it',
        ])->assertOk();

        $customerId = $response->json('customer.id');

        $this->assertNotNull($customerId);
        $this->assertDatabaseHas('orders', [
            'email' => 'anna@example.com',
            'customer_id' => $customerId,
        ]);
        $this->assertDatabaseHas('reservations', [
            'email' => 'anna@example.com',
            'customer_id' => $customerId,
        ]);
        $this->assertSame(
            'soft_marketing',
            Customer::query()->findOrFail($customerId)->marketingState()
        );
    }

    public function test_customer_can_complete_profile_and_disable_consents(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/send-otp', [
            'email' => 'profile@example.com',
            'intent' => 'account',
            'lang' => 'it',
        ])->assertOk();

        $otpCode = null;
        Mail::assertSent(CustomerOtpMail::class, function (CustomerOtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->code;

            return true;
        });

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'profile@example.com',
            'code' => $otpCode,
            'intent' => 'account',
            'lang' => 'it',
        ])->assertOk();

        $token = $response->json('token');
        $customerId = $response->json('customer.id');

        $this->putJson('/api/auth/profile', [
            'name' => 'Mario',
            'surname' => 'Rossi',
            'phone' => '3339876543',
            'gender' => 'male',
            'birthday' => '1992-05-21',
            'profile_answers' => [],
            'marketing_enabled' => true,
            'profiling_enabled' => true,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.account_state', 'registered')
            ->assertJsonPath('customer.marketing_state', 'full')
            ->assertJsonPath('customer.email_marketing_enabled', true)
            ->assertJsonPath('customer.profiling_enabled', true);

        $this->patchJson('/api/auth/consents', [
            'marketing_enabled' => false,
            'profiling_enabled' => false,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.marketing_state', 'no_marketing')
            ->assertJsonPath('customer.marketing_enabled', false)
            ->assertJsonPath('customer.profiling_enabled', false);

        $customer = Customer::query()->findOrFail($customerId);
        $this->assertNotNull($customer->registered_at);
        $this->assertNotNull($customer->marketing_consent_at);
        $this->assertNull($customer->email_marketing_consent_at);
        $this->assertNull($customer->profiling_consent_at);
        $this->assertNotNull($customer->consents_updated_at);
    }

    public function test_account_update_consents_true_sets_explicit_customer_consents(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Account',
            'surname' => 'Consensi',
            'email' => 'account-consents@example.com',
            'phone' => '3331112222',
        ]);
        $token = $customer->createToken('customer-api')->plainTextToken;

        $this->patchJson('/api/auth/consents', [
            'email_marketing_enabled' => true,
            'whatsapp_marketing_enabled' => true,
            'profiling_enabled' => true,
            'tracking_enabled' => true,
            'privacy_accepted' => true,
            'privacy_version' => 'privacy-v2',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.email_marketing_enabled', true)
            ->assertJsonPath('customer.whatsapp_marketing_enabled', true)
            ->assertJsonPath('customer.profiling_enabled', true)
            ->assertJsonPath('customer.tracking_enabled', true)
            ->assertJsonPath('customer.privacy_accepted', true)
            ->assertJsonPath('customer.privacy_accepted_version', 'privacy-v2');

        $customer->refresh();
        $this->assertNotNull($customer->email_marketing_consent_at);
        $this->assertNotNull($customer->marketing_consent_at);
        $this->assertNotNull($customer->whatsapp_marketing_consent_at);
        $this->assertNotNull($customer->profiling_consent_at);
        $this->assertNotNull($customer->tracking_consent_at);
        $this->assertNotNull($customer->privacy_accepted_at);
        $this->assertSame('privacy-v2', $customer->privacy_accepted_version);
        $this->assertNotNull($customer->consents_updated_at);
    }

    public function test_profile_questions_accept_only_configured_choice_answers(): void
    {
        Setting::query()->updateOrCreate(
            ['name' => 'customer_profile'],
            [
                'status' => 1,
                'property' => json_encode([
                    'questions' => [
                        [
                            'key' => 'piatto_preferito',
                            'label' => 'Piatto preferito',
                            'type' => 'single_choice',
                            'required' => true,
                            'options' => [
                                ['key' => 'pizza', 'label' => 'Pizza'],
                                ['key' => 'pasta', 'label' => 'Pasta'],
                            ],
                        ],
                        [
                            'key' => 'occasioni',
                            'label' => 'Occasioni',
                            'type' => 'multiple_choice',
                            'required' => false,
                            'options' => [
                                ['key' => 'pranzo', 'label' => 'Pranzo'],
                                ['key' => 'cena', 'label' => 'Cena'],
                            ],
                        ],
                    ],
                ]),
            ]
        );

        $customer = Customer::query()->create([
            'name' => 'Choice',
            'surname' => 'Customer',
            'email' => 'choice-customer@example.com',
            'phone' => '3331112222',
        ]);
        $token = $customer->createToken('customer-api')->plainTextToken;

        $payload = [
            'name' => 'Choice',
            'surname' => 'Customer',
            'phone' => '3331112222',
            'gender' => 'prefer_not_to_say',
            'birthday' => '1990-01-15',
            'profile_answers' => [
                'piatto_preferito' => 'testo_libero_non_configurato',
                'occasioni' => ['cena'],
            ],
        ];

        $this->putJson('/api/auth/profile', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(422);

        $payload['profile_answers'] = [
            'piatto_preferito' => 'pizza',
            'occasioni' => ['cena', 'testo_libero_non_configurato'],
        ];

        $this->putJson('/api/auth/profile', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.profile_answers.piatto_preferito', 'pizza')
            ->assertJsonPath('customer.profile_answers.occasioni.0', 'cena');

        $customer->refresh();
        $this->assertSame([
            'piatto_preferito' => 'pizza',
            'occasioni' => ['cena'],
        ], $customer->profile_answers);
    }

    public function test_account_update_consents_false_revokes_optional_consents_only(): void
    {
        $originalConsentAt = now()->subDay()->startOfSecond();

        $customer = Customer::query()->create([
            'name' => 'Account',
            'surname' => 'Revoche',
            'email' => 'account-revokes@example.com',
            'phone' => '3331112222',
            'marketing_consent_at' => $originalConsentAt,
            'email_marketing_consent_at' => $originalConsentAt,
            'whatsapp_marketing_consent_at' => $originalConsentAt,
            'profiling_consent_at' => $originalConsentAt,
            'tracking_consent_at' => $originalConsentAt,
            'privacy_accepted_at' => $originalConsentAt,
            'privacy_accepted_version' => 'privacy-v1',
        ]);
        $token = $customer->createToken('customer-api')->plainTextToken;

        $this->patchJson('/api/auth/consents', [
            'email_marketing_enabled' => false,
            'whatsapp_marketing_enabled' => false,
            'profiling_enabled' => false,
            'tracking_enabled' => false,
            'privacy_accepted' => false,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.marketing_enabled', false)
            ->assertJsonPath('customer.email_marketing_enabled', false)
            ->assertJsonPath('customer.whatsapp_marketing_enabled', false)
            ->assertJsonPath('customer.profiling_enabled', false)
            ->assertJsonPath('customer.tracking_enabled', false)
            ->assertJsonPath('customer.privacy_accepted', true);

        $customer->refresh();
        $this->assertSame($originalConsentAt->toDateTimeString(), $customer->marketing_consent_at->toDateTimeString());
        $this->assertNull($customer->email_marketing_consent_at);
        $this->assertNull($customer->whatsapp_marketing_consent_at);
        $this->assertNull($customer->profiling_consent_at);
        $this->assertNull($customer->tracking_consent_at);
        $this->assertSame($originalConsentAt->toDateTimeString(), $customer->privacy_accepted_at->toDateTimeString());
        $this->assertSame('privacy-v1', $customer->privacy_accepted_version);
        $this->assertNotNull($customer->consents_updated_at);
    }

    public function test_account_legacy_marketing_enabled_updates_email_marketing_consent(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Legacy',
            'surname' => 'Marketing',
            'email' => 'legacy-marketing@example.com',
            'phone' => '3331112222',
        ]);
        $token = $customer->createToken('customer-api')->plainTextToken;

        $this->patchJson('/api/auth/consents', [
            'marketing_enabled' => true,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.marketing_enabled', true)
            ->assertJsonPath('customer.email_marketing_enabled', true);

        $customer->refresh();
        $this->assertNotNull($customer->email_marketing_consent_at);
        $this->assertNotNull($customer->marketing_consent_at);
    }

    public function test_auth_me_response_contains_explicit_consents_and_settings(): void
    {
        $consentAt = now()->subHour()->startOfSecond();
        $customer = Customer::query()->create([
            'name' => 'Me',
            'surname' => 'Consensi',
            'email' => 'me-consents@example.com',
            'phone' => '3331112222',
            'email_marketing_consent_at' => $consentAt,
            'whatsapp_marketing_consent_at' => $consentAt,
            'profiling_consent_at' => $consentAt,
            'tracking_consent_at' => $consentAt,
            'privacy_accepted_at' => $consentAt,
            'privacy_accepted_version' => 'privacy-v3',
            'soft_email_marketing_unsubscribed_at' => $consentAt,
        ]);
        $token = $customer->createToken('customer-api')->plainTextToken;

        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('customer.email_marketing_enabled', true)
            ->assertJsonPath('customer.whatsapp_marketing_enabled', true)
            ->assertJsonPath('customer.profiling_enabled', true)
            ->assertJsonPath('customer.tracking_enabled', true)
            ->assertJsonPath('customer.privacy_accepted', true)
            ->assertJsonPath('customer.privacy_accepted_version', 'privacy-v3')
            ->assertJsonPath('customer.soft_email_marketing_unsubscribed', true)
            ->assertJsonPath('customer.soft_email_marketing_unsubscribed_at', $consentAt->toISOString())
            ->assertJsonPath('profile_settings.email_marketing_label', 'Acconsento a ricevere via email novità, offerte e promozioni del ristorante.')
            ->assertJsonPath('profile_settings.accept_all_label', 'Accetta tutti i consensi facoltativi');
    }

    public function test_customer_payload_exposes_missing_soft_email_opt_out_as_false(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Soft',
            'surname' => 'Optout',
            'email' => 'soft-optout@example.com',
            'phone' => '3331112222',
        ]);

        $payload = $customer->fresh()->toApiPayload();

        $this->assertFalse($payload['soft_email_marketing_unsubscribed']);
        $this->assertNull($payload['soft_email_marketing_unsubscribed_at']);
    }

    public function test_customer_profile_settings_ignore_custom_consent_texts_and_keep_questions(): void
    {
        Setting::query()->updateOrCreate(
            ['name' => 'customer_profile'],
            [
                'status' => 1,
                'property' => json_encode([
                    'marketing_consent_text' => 'Testo marketing custom',
                    'profiling_consent_text' => 'Testo profilazione custom',
                    'email_marketing_label' => 'Label email custom',
                    'questions' => [
                        [
                            'key' => 'piatto_preferito',
                            'label' => 'Piatto preferito',
                            'type' => 'single_choice',
                            'required' => true,
                            'options' => [
                                ['key' => 'pizza', 'label' => 'Pizza'],
                                ['key' => 'pasta', 'label' => 'Pasta'],
                            ],
                        ],
                    ],
                ]),
            ]
        );

        $settings = app(CustomerProfileSettingsService::class)->get();

        $this->assertSame(
            'Acconsento a ricevere via email novità, offerte e promozioni del ristorante.',
            $settings['marketing_consent_text']
        );
        $this->assertSame(
            'Acconsento all\'uso delle mie preferenze, risposte al questionario e storico ordini/prenotazioni per ricevere offerte e comunicazioni personalizzate.',
            $settings['profiling_consent_text']
        );
        $this->assertSame('piatto_preferito', $settings['questions'][0]['key']);
        $this->assertSame('Piatto preferito', $settings['questions'][0]['label']);
        $this->assertSame('single_choice', $settings['questions'][0]['type']);
        $this->assertTrue($settings['questions'][0]['required']);
        $this->assertSame('pizza', $settings['questions'][0]['options'][0]['key']);
    }

    public function test_admin_profile_settings_update_ignores_consent_text_fields(): void
    {
        Setting::query()->updateOrCreate(
            ['name' => 'customer_profile'],
            [
                'status' => 1,
                'property' => json_encode([
                    'marketing_consent_text' => 'Testo marketing precedente',
                    'profiling_consent_text' => 'Testo profilazione precedente',
                    'questions' => [],
                ]),
            ]
        );

        $this->actingAs(User::factory()->create())
            ->post('/admin/customers/profile-settings', [
                'marketing_consent_text' => 'Nuovo testo marketing da ignorare',
                'profiling_consent_text' => 'Nuovo testo profilazione da ignorare',
                'email_marketing_label' => 'Nuova label da ignorare',
                'questions' => [
                    [
                        'key' => '',
                        'label' => 'Allergie o preferenze',
                        'type' => 'multiple_choice',
                        'required' => '1',
                        'options' => [
                            ['key' => '', 'label' => 'Senza glutine'],
                            ['key' => '', 'label' => 'Vegetariano'],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.customers.index'));

        $property = json_decode(Setting::query()->where('name', 'customer_profile')->firstOrFail()->property, true);

        $this->assertSame('Testo marketing precedente', $property['marketing_consent_text']);
        $this->assertSame('Testo profilazione precedente', $property['profiling_consent_text']);
        $this->assertArrayNotHasKey('email_marketing_label', $property);
        $this->assertSame('allergie_o_preferenze', $property['questions'][0]['key']);
        $this->assertSame('multiple_choice', $property['questions'][0]['type']);
        $this->assertSame('senza_glutine', $property['questions'][0]['options'][0]['key']);
        $this->assertTrue($property['questions'][0]['required']);
    }

    public function test_checkout_consents_store_explicit_customer_consents(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Consenso',
            'surname' => 'Esplicito',
            'email' => 'explicit-consents@example.com',
            'phone' => '3331112222',
        ]);

        $updated = app(CustomerAccessService::class)->applyCheckoutConsents($customer, [
            'privacy_accepted' => true,
            'privacy_version' => 'privacy-v1',
            'email_marketing_enabled' => true,
            'whatsapp_marketing_enabled' => true,
            'profiling_enabled' => true,
            'tracking_enabled' => true,
        ]);

        $this->assertNotNull($updated->privacy_accepted_at);
        $this->assertSame('privacy-v1', $updated->privacy_accepted_version);
        $this->assertNotNull($updated->email_marketing_consent_at);
        $this->assertNotNull($updated->marketing_consent_at);
        $this->assertNotNull($updated->whatsapp_marketing_consent_at);
        $this->assertNotNull($updated->profiling_consent_at);
        $this->assertNotNull($updated->tracking_consent_at);
        $this->assertNotNull($updated->consents_updated_at);
    }

    public function test_checkout_legacy_newsletter_sets_email_marketing_consent(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Newsletter',
            'surname' => 'Legacy',
            'email' => 'legacy-newsletter@example.com',
            'phone' => '3331112222',
        ]);

        $updated = app(CustomerAccessService::class)->applyCheckoutConsents($customer, [
            'news_letter' => true,
        ]);

        $this->assertNotNull($updated->email_marketing_consent_at);
        $this->assertNotNull($updated->marketing_consent_at);
        $this->assertNotNull($updated->consents_updated_at);
    }

    public function test_checkout_false_values_do_not_revoke_existing_consents(): void
    {
        $originalConsentAt = now()->subDay()->startOfSecond();

        $customer = Customer::query()->create([
            'name' => 'No',
            'surname' => 'Revoca',
            'email' => 'no-revoke@example.com',
            'phone' => '3331112222',
            'marketing_consent_at' => $originalConsentAt,
            'email_marketing_consent_at' => $originalConsentAt,
            'whatsapp_marketing_consent_at' => $originalConsentAt,
            'profiling_consent_at' => $originalConsentAt,
            'tracking_consent_at' => $originalConsentAt,
            'privacy_accepted_at' => $originalConsentAt,
            'privacy_accepted_version' => 'privacy-v1',
        ]);

        $updated = app(CustomerAccessService::class)->applyCheckoutConsents($customer, [
            'privacy_accepted' => false,
            'email_marketing_enabled' => false,
            'whatsapp_marketing_enabled' => false,
            'profiling_enabled' => false,
            'tracking_enabled' => false,
        ]);

        $this->assertSame($originalConsentAt->toDateTimeString(), $updated->privacy_accepted_at->toDateTimeString());
        $this->assertSame('privacy-v1', $updated->privacy_accepted_version);
        $this->assertSame($originalConsentAt->toDateTimeString(), $updated->marketing_consent_at->toDateTimeString());
        $this->assertSame($originalConsentAt->toDateTimeString(), $updated->email_marketing_consent_at->toDateTimeString());
        $this->assertSame($originalConsentAt->toDateTimeString(), $updated->whatsapp_marketing_consent_at->toDateTimeString());
        $this->assertSame($originalConsentAt->toDateTimeString(), $updated->profiling_consent_at->toDateTimeString());
        $this->assertSame($originalConsentAt->toDateTimeString(), $updated->tracking_consent_at->toDateTimeString());
        $this->assertNull($updated->consents_updated_at);
    }

    public function test_checkout_legacy_payload_without_privacy_is_still_accepted_for_now(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Payload',
            'surname' => 'Legacy',
            'email' => 'legacy-payload@example.com',
            'phone' => '3331112222',
        ]);

        $updated = app(CustomerAccessService::class)->applyCheckoutConsents($customer, [
            'news_letter' => false,
        ]);

        $this->assertNull($updated->privacy_accepted_at);
        $this->assertNull($updated->email_marketing_consent_at);
        $this->assertNull($updated->consents_updated_at);
    }

    public function test_customer_lookup_keeps_email_as_primary_identifier(): void
    {
        $service = app(CustomerAccessService::class);

        $first = $service->findOrCreateForVerifiedCheckout('SAME@example.com', [
            'name' => 'Anna',
            'surname' => 'Rossi',
            'phone' => '3331112222',
        ]);

        $second = $service->findOrCreateForVerifiedCheckout('same@example.com', [
            'name' => 'Anna',
            'surname' => 'Rossi',
            'phone' => '3339998888',
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('3331112222', $second->phone);
        $this->assertSame(1, Customer::query()->where('email', 'same@example.com')->count());
    }

    public function test_customer_lookup_does_not_merge_different_emails_with_same_phone(): void
    {
        $service = app(CustomerAccessService::class);

        $first = $service->findOrCreateForVerifiedCheckout('first@example.com', [
            'name' => 'Mario',
            'surname' => 'Rossi',
            'phone' => '3331112222',
        ]);

        $second = $service->findOrCreateForVerifiedCheckout('second@example.com', [
            'name' => 'Anna',
            'surname' => 'Bianchi',
            'phone' => '+39 333 111 2222',
        ]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Customer::query()->count());
    }

    public function test_phone_lookup_is_only_a_fallback_for_customers_without_email(): void
    {
        $service = app(CustomerAccessService::class);

        $withoutEmail = Customer::query()->create([
            'name' => 'Telefono',
            'surname' => 'Solo',
            'email' => '',
            'phone' => '3331112222',
        ]);

        $this->assertSame(
            $withoutEmail->id,
            $service->findExistingCustomer(null, '+39 333 111 2222')?->id
        );
    }

    public function test_phone_lookup_does_not_merge_when_existing_customer_has_email(): void
    {
        $service = app(CustomerAccessService::class);

        Customer::query()->create([
            'name' => 'Cliente',
            'surname' => 'Email',
            'email' => 'with-email@example.com',
            'phone' => '3331112222',
        ]);

        $this->assertNull($service->findExistingCustomer(null, '+39 333 111 2222'));
    }
}
