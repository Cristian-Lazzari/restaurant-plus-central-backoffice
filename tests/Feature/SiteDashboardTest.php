<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_sites_in_saved_sort_order(): void
    {
        Site::create([
            'name' => 'Alpha',
            'sort_order' => 2,
            'url' => 'https://alpha.test',
            'token' => 'alpha-token',
            'active' => true,
        ]);

        Site::create([
            'name' => 'Bravo',
            'sort_order' => 1,
            'url' => 'https://bravo.test',
            'token' => 'bravo-token',
            'active' => true,
        ]);

        $this
            ->withSession(['backoffice_authenticated' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Bravo', 'Alpha']);
    }

    public function test_sites_can_be_reordered_from_dashboard(): void
    {
        $alpha = Site::create([
            'name' => 'Alpha',
            'sort_order' => 1,
            'url' => 'https://alpha.test',
            'token' => 'alpha-token',
            'active' => true,
        ]);

        $bravo = Site::create([
            'name' => 'Bravo',
            'sort_order' => 2,
            'url' => 'https://bravo.test',
            'token' => 'bravo-token',
            'active' => true,
        ]);

        $this
            ->withSession(['backoffice_authenticated' => true])
            ->post(route('sites.reorder'), [
                'site_ids' => [$bravo->id, $alpha->id],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(2, $alpha->fresh()->sort_order);
        $this->assertSame(1, $bravo->fresh()->sort_order);
    }
}
