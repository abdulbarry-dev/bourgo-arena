<?php

namespace Tests\Feature\Api\Admin;

use App\Models\CheckInEvent;
use App\Models\Member;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_admin_can_view_all_audit_logs(): void
    {
        CheckInEvent::factory()->count(10)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.audit-logs.index'));

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    public function test_admin_can_view_specific_member_audit_logs(): void
    {
        $member = Member::factory()->create();
        CheckInEvent::factory()->count(3)->create(['member_id' => $member->id]);
        CheckInEvent::factory()->count(2)->create(); // Other events

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.members.audit-logs', $member));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_audit_logs_by_suspicious_status(): void
    {
        CheckInEvent::factory()->count(2)->create(['is_suspicious' => true]);
        CheckInEvent::factory()->count(3)->create(['is_suspicious' => false]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.audit-logs.index', ['is_suspicious' => true]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
