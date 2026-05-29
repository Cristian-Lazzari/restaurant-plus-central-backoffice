<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use App\Services\Crm\CustomerSegmentService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSegmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-26 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_service_assigns_the_main_crm_segments(): void
    {
        $this->createOrder([
            'name' => 'Nina',
            'surname' => 'Nuova',
            'email' => 'new@example.com',
            'phone' => '3330000001',
            'tot_price' => 50,
            'date' => now()->subDays(5),
        ]);

        $this->createOrder([
            'name' => 'Alberto',
            'surname' => 'Attivo',
            'email' => 'active@example.com',
            'phone' => '3330000002',
            'tot_price' => 35,
            'date' => now()->subDays(7),
        ]);
        $this->createReservation([
            'name' => 'Alberto',
            'surname' => 'Attivo',
            'email' => 'active@example.com',
            'phone' => '3330000002',
            'date' => now()->subDays(2),
        ]);

        foreach ([25, 20, 15, 10, 5] as $index => $daysAgo) {
            $this->createOrder([
                'name' => 'Lucia',
                'surname' => 'Fedele',
                'email' => 'loyal@example.com',
                'phone' => '3330000003',
                'tot_price' => 20,
                'date' => now()->subDays($daysAgo)->setTime(20, $index),
            ]);
        }

        $this->createOrder([
            'name' => 'Roberto',
            'surname' => 'Rischio',
            'email' => 'risk@example.com',
            'phone' => '3330000004',
            'tot_price' => 30,
            'date' => now()->subDays(45),
        ]);

        $this->createReservation([
            'name' => 'Laura',
            'surname' => 'Persa',
            'email' => 'lost@example.com',
            'phone' => '3330000005',
            'date' => now()->subDays(75),
        ]);

        $this->createOrder([
            'name' => 'Valerio',
            'surname' => 'Premium',
            'email' => 'high@example.com',
            'phone' => '3330000006',
            'tot_price' => 80,
            'date' => now()->subDays(8),
        ]);
        $this->createOrder([
            'name' => 'Valerio',
            'surname' => 'Premium',
            'email' => 'high@example.com',
            'phone' => '3330000006',
            'tot_price' => 60,
            'date' => now()->subDays(3),
        ]);

        foreach ([20, 13, 6] as $daysAgo) {
            $this->createReservation([
                'name' => 'Chiara',
                'surname' => 'Abitudine',
                'email' => 'habit@example.com',
                'phone' => '3330000007',
                'date' => now()->subDays($daysAgo)->setTime(20, 0),
            ]);
        }

        $this->createReservation([
            'name' => 'Paolo',
            'surname' => 'Freddo',
            'email' => 'low@example.com',
            'phone' => '3330000008',
            'date' => now()->subDays(7),
        ]);

        $profiles = $this->crmService()->buildBaseCustomerQuery();

        $this->assertProfileHasSegment($profiles, 'new@example.com', 'new_customers');
        $this->assertProfileHasSegment($profiles, 'active@example.com', 'active_customers');
        $this->assertProfileHasSegment($profiles, 'loyal@example.com', 'loyal_customers');
        $this->assertProfileHasSegment($profiles, 'risk@example.com', 'at_risk_customers');
        $this->assertProfileHasSegment($profiles, 'lost@example.com', 'lost_customers');
        $this->assertProfileHasSegment($profiles, 'high@example.com', 'high_value_customers');
        $this->assertProfileHasSegment($profiles, 'habit@example.com', 'habit_customers');
        $this->assertProfileHasSegment($profiles, 'low@example.com', 'low_engagement');

        $this->assertSame(1, $this->crmService()->countCustomersForSegment('high_value_customers'));
        $this->assertSame('High value', $this->crmService()->getSegmentLabel('high_value_customers'));
    }

    public function test_service_distinguishes_order_only_reservation_only_and_customers_with_both_channels(): void
    {
        $this->createOrder([
            'name' => 'Olivia',
            'surname' => 'Ordini',
            'email' => 'order-only@example.com',
            'phone' => '3331000001',
            'tot_price' => 22,
            'date' => now()->subDays(5),
        ]);
        $this->createOrder([
            'name' => 'Olivia',
            'surname' => 'Ordini',
            'email' => 'order-only@example.com',
            'phone' => '3331000001',
            'tot_price' => 18,
            'date' => now()->subDays(2),
        ]);

        $this->createReservation([
            'name' => 'Rita',
            'surname' => 'Prenota',
            'email' => 'reservation-only@example.com',
            'phone' => '3331000002',
            'date' => now()->subDays(6),
        ]);
        $this->createReservation([
            'name' => 'Rita',
            'surname' => 'Prenota',
            'email' => 'reservation-only@example.com',
            'phone' => '3331000002',
            'date' => now()->subDays(1),
        ]);

        $this->createOrder([
            'name' => 'Biagio',
            'surname' => 'Misto',
            'email' => 'both@example.com',
            'phone' => '3331000003',
            'tot_price' => 25,
            'date' => now()->subDays(4),
        ]);
        $this->createReservation([
            'name' => 'Biagio',
            'surname' => 'Misto',
            'email' => 'both@example.com',
            'phone' => '3331000003',
            'date' => now()->subDays(2),
        ]);

        $profiles = $this->crmService()->buildBaseCustomerQuery();

        $orderOnly = $this->findProfileByEmail($profiles, 'order-only@example.com');
        $reservationOnly = $this->findProfileByEmail($profiles, 'reservation-only@example.com');
        $both = $this->findProfileByEmail($profiles, 'both@example.com');

        $this->assertContains('order_only', $orderOnly->segments);
        $this->assertNotContains('reservation_only', $orderOnly->segments);
        $this->assertContains('reservation_only', $reservationOnly->segments);
        $this->assertNotContains('order_only', $reservationOnly->segments);
        $this->assertNotContains('order_only', $both->segments);
        $this->assertNotContains('reservation_only', $both->segments);
        $this->assertSame(1, $both->orders_count);
        $this->assertSame(1, $both->reservations_count);
    }

    public function test_admin_customer_search_works_together_with_segment_filter_and_keeps_pagination_params(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        for ($index = 1; $index <= 16; $index++) {
            $this->createOrder([
                'name' => 'Mario',
                'surname' => sprintf('Premium %02d', $index),
                'email' => sprintf('mario-premium-%02d@example.com', $index),
                'phone' => sprintf('333200%04d', $index),
                'tot_price' => 120,
                'date' => now()->subDays($index),
            ]);
        }

        $this->createOrder([
            'name' => 'Mario',
            'surname' => 'Base',
            'email' => 'mario-base@example.com',
            'phone' => '3332999999',
            'tot_price' => 20,
            'date' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.customers.index', [
            'search' => 'Mario',
            'segment' => 'high_value_customers',
            'type' => 'all',
        ]));

        $response
            ->assertOk()
            ->assertSee('Mario Premium 01')
            ->assertDontSee('Mario Base')
            ->assertSee('search=Mario', false)
            ->assertSee('segment=high_value_customers', false)
            ->assertSee('page=2', false);

        $this->actingAs($admin)
            ->get(route('admin.customers.index', [
                'search' => 'Mario',
                'segment' => 'high_value_customers',
                'type' => 'all',
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('Mario Premium 16')
            ->assertDontSee('Mario Base');
    }

    public function test_service_deduplicates_registered_and_guest_profiles_by_email_without_merging_different_emails_by_phone(): void
    {
        $customer = $this->createRegisteredCustomer([
            'name' => 'Anna',
            'surname' => 'Rossi',
            'email' => 'anna@example.com',
            'phone' => '333 111 2222',
        ]);

        $this->createOrder([
            'customer_id' => null,
            'name' => 'Anna',
            'surname' => 'Rossi',
            'email' => 'ANNA@example.com',
            'phone' => '3339998888',
            'tot_price' => 35,
            'date' => now()->subDays(3),
        ]);

        $this->createReservation([
            'customer_id' => null,
            'name' => 'Anna',
            'surname' => 'Rossi',
            'email' => 'anna.secondary@example.com',
            'phone' => '+39 333 111 2222',
            'date' => now()->subDay(),
        ]);

        $profiles = $this->crmService()->buildBaseCustomerQuery();
        $profile = $profiles->firstWhere('customer_id', $customer->id);

        $this->assertNotNull($profile);
        $this->assertSame(1, $profiles->where('customer_id', $customer->id)->count());
        $this->assertSame('anna@example.com', $profile->email);
        $this->assertSame(1, $profile->orders_count);
        $this->assertSame(0, $profile->reservations_count);
        $this->assertSame(1, $profile->interactions_count);

        $secondaryProfile = $this->findProfileByEmail($profiles, 'anna.secondary@example.com');

        $this->assertNull($secondaryProfile->customer_id);
        $this->assertSame(0, $secondaryProfile->orders_count);
        $this->assertSame(1, $secondaryProfile->reservations_count);
        $this->assertSame(1, $secondaryProfile->interactions_count);
    }

    public function test_service_uses_phone_fallback_only_when_email_is_missing(): void
    {
        $customer = $this->createRegisteredCustomer([
            'name' => 'Telefono',
            'surname' => 'Solo',
            'email' => '',
            'phone' => '333 111 2222',
        ]);

        $this->createOrder([
            'customer_id' => null,
            'name' => 'Telefono',
            'surname' => 'Solo',
            'email' => '',
            'phone' => '+39 333 111 2222',
            'tot_price' => 35,
            'date' => now()->subDays(3),
        ]);

        $profiles = $this->crmService()->buildBaseCustomerQuery();
        $profile = $profiles->firstWhere('customer_id', $customer->id);

        $this->assertNotNull($profile);
        $this->assertSame(1, $profile->orders_count);
        $this->assertSame(1, $profile->interactions_count);
    }

    public function test_zero_cached_customer_score_falls_back_to_real_orders_and_reservations(): void
    {
        if (! Schema::hasColumn('customers', 'customer_score')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedInteger('customer_score')->default(0);
            });
        }

        $customer = $this->createRegisteredCustomer([
            'name' => 'Giulio',
            'surname' => 'Fallback',
            'email' => 'score-zero@example.com',
            'phone' => '3333000001',
        ]);

        DB::table('customers')
            ->where('id', $customer->id)
            ->update(['customer_score' => 0]);

        foreach ([15, 8, 2] as $daysAgo) {
            $this->createOrder([
                'customer_id' => $customer->id,
                'name' => 'Giulio',
                'surname' => 'Fallback',
                'email' => 'score-zero@example.com',
                'phone' => '3333000001',
                'tot_price' => 50,
                'date' => now()->subDays($daysAgo),
            ]);
        }

        $profile = $this->crmService()->buildBaseCustomerQuery()->firstWhere('customer_id', $customer->id);

        $this->assertNotNull($profile);
        $this->assertGreaterThan(0, $profile->customer_score);
        $this->assertContains('high_value_customers', $profile->segments);
        $this->assertContains('loyal_customers', $profile->segments);
    }

    private function crmService(): CustomerSegmentService
    {
        return app(CustomerSegmentService::class);
    }

    private function createRegisteredCustomer(array $overrides = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Cliente',
            'surname' => 'Registrato',
            'email' => 'registered-'.uniqid().'@example.com',
            'phone' => '333'.random_int(1000000, 9999999),
            'gender' => null,
            'birthday' => null,
            'profile_answers' => [],
            'registered_at' => now()->subMonth(),
            'marketing_consent_at' => null,
            'profiling_consent_at' => null,
            'email_verified_at' => now()->subMonth(),
        ], $overrides));
    }

    private function createOrder(array $overrides = []): int
    {
        $date = $overrides['date'] ?? now();
        unset($overrides['date']);

        return DB::table('orders')->insertGetId(array_merge([
            'customer_id' => null,
            'date_slot' => $this->formatDate($date),
            'status' => 1,
            'name' => 'Cliente',
            'surname' => 'Ordine',
            'email' => 'order-'.uniqid().'@example.com',
            'phone' => '333'.random_int(1000000, 9999999),
            'checkout_session_id' => null,
            'address' => null,
            'address_n' => null,
            'comune' => null,
            'whatsapp_message_id' => null,
            'tot_price' => 25.0,
            'message' => null,
            'news_letter' => false,
            'notificated' => false,
            'created_at' => $date,
            'updated_at' => $date,
        ], $overrides));
    }

    private function createReservation(array $overrides = []): int
    {
        $date = $overrides['date'] ?? now();
        unset($overrides['date']);

        return DB::table('reservations')->insertGetId(array_merge([
            'customer_id' => null,
            'date_slot' => $this->formatDate($date),
            'status' => 1,
            'name' => 'Cliente',
            'surname' => 'Prenotazione',
            'email' => 'reservation-'.uniqid().'@example.com',
            'phone' => '333'.random_int(1000000, 9999999),
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

    private function formatDate(Carbon $date): string
    {
        return $date->format('d/m/Y H:i');
    }

    private function assertProfileHasSegment(Collection $profiles, string $email, string $segment): void
    {
        $profile = $this->findProfileByEmail($profiles, $email);

        $this->assertContains($segment, $profile->segments);
    }

    private function findProfileByEmail(Collection $profiles, string $email): object
    {
        $normalized = Customer::normalizeEmail($email);
        $profile = $profiles->first(function ($profile) use ($normalized) {
            return Customer::normalizeEmail((string) $profile->email) === $normalized;
        });

        $this->assertNotNull($profile, 'Profilo non trovato per '.$email);

        return $profile;
    }
}
