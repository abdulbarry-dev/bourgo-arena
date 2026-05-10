<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Member;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
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

    public function test_admin_can_list_members(): void
    {
        Member::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.members.index'));

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_filter_members_by_search(): void
    {
        Member::factory()->create(['name' => 'John Doe']);
        Member::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.members.index', ['search' => 'John']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    public function test_admin_can_show_member_details(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.admin.members.show', $member));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $member->id);
    }

    public function test_admin_can_update_member_status(): void
    {
        $member = Member::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('api.admin.members.update-status', $member), [
                'status' => 'suspended',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('suspended', $member->fresh()->status);
    }

    public function test_admin_can_delete_member(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('api.admin.members.destroy', $member));

        $response->assertStatus(200);
        $this->assertSoftDeleted('members', ['id' => $member->id]);
    }

    public function test_unauthorized_users_cannot_access_member_management(): void
    {
        $user = User::factory()->create(); // No admin role

        $response = $this->actingAs($user)
            ->getJson(route('api.admin.members.index'));

        $response->assertStatus(403);
    }
}
