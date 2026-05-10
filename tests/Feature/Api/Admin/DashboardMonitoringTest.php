<?php

namespace Tests\Feature\Api\Admin;

use App\Models\CheckInEvent;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
    }

    public function test_admin_can_view_live_feed(): void
    {
        CheckInEvent::factory()->count(25)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.live-feed'));

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data'); // Limited to 20
    }

    public function test_admin_can_view_occupancy_stats(): void
    {
        $dateStr = now()->toDateString();
        Cache::put("gym:occupancy:{$dateStr}", 42);

        config(['app.gym_capacity' => 100]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.occupancy'));

        $response->assertStatus(200)
            ->assertJsonPath('data.current', 42)
            ->assertJsonPath('data.capacity', 100)
            ->assertJsonPath('data.percentage', 42);
    }
}
