<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->manager = User::factory()->create(['role' => UserRole::Manager]);
    }

    /**
     * Test admin can access member resources.
     */
    public function test_admin_can_view_members(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('viewAny', Member::class));
    }

    /**
     * Test manager can view members.
     */
    public function test_manager_can_view_members(): void
    {
        $this->actingAs($this->manager);
        $this->assertTrue($this->manager->can('viewAny', Member::class));
    }

    /**
     * Test admin and manager can create members.
     */
    public function test_admin_and_manager_can_create_members(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('create', Member::class));

        $this->actingAs($this->manager);
        $this->assertTrue($this->manager->can('create', Member::class));
    }

    /**
     * Test admin can enroll subscriptions.
     */
    public function test_admin_can_enroll_subscription(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('create', Subscription::class));
    }

    /**
     * Test manager can enroll subscriptions.
     */
    public function test_manager_can_enroll_subscription(): void
    {
        $this->actingAs($this->manager);
        $this->assertTrue($this->manager->can('create', Subscription::class));
    }

    /**
     * Test only admin can transfer subscriptions.
     */
    public function test_only_admin_can_transfer_subscription(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('transfer', Subscription::class));

        $this->actingAs($this->manager);
        $this->assertFalse($this->manager->can('transfer', Subscription::class));
    }

    /**
     * Test member delete only allowed for admin.
     */
    public function test_only_admin_can_delete_members(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('delete', $member));

        $this->actingAs($this->manager);
        $this->assertFalse($this->manager->can('delete', $member));
    }

    /**
     * Test member suspend/activate for managers.
     */
    public function test_manager_can_suspend_and_activate_members(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->manager);
        $this->assertTrue($this->manager->can('suspend', $member));
        $this->assertTrue($this->manager->can('activate', $member));
    }

    /**
     * Test only admin can view payment details.
     */
    public function test_only_admin_can_view_payment_details(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('viewPayment', Subscription::class));

        $this->actingAs($this->manager);
        $this->assertFalse($this->manager->can('viewPayment', Subscription::class));
    }

    /**
     * Test that unauthorized roles are denied.
     */
    public function test_authorization_boundaries(): void
    {
        // Verify manager cannot delete (admin only)
        $member = Member::factory()->create();
        $this->actingAs($this->manager);
        $this->assertFalse($this->manager->can('delete', $member));
    }

    /**
     * Test admin methods return correct boolean values.
     */
    public function test_user_role_helper_methods(): void
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertFalse($this->admin->isManager());
        $this->assertTrue($this->admin->isStaff());

        $this->assertFalse($this->manager->isAdmin());
        $this->assertTrue($this->manager->isManager());
        $this->assertTrue($this->manager->isStaff());
    }

    /**
     * Test dashboard module access helpers.
     */
    public function test_user_dashboard_module_access_helpers(): void
    {
        $this->assertTrue($this->admin->can('access-dashboard-module', 'members'));
        $this->assertTrue($this->admin->can('access-dashboard-module', 'courses'));
        $this->assertTrue($this->admin->can('access-dashboard-module', 'managers'));

        $this->assertTrue($this->manager->can('access-dashboard-module', 'members'));
        $this->assertTrue($this->manager->can('access-dashboard-module', 'schedule'));
        $this->assertFalse($this->manager->can('access-dashboard-module', 'courses'));
        $this->assertFalse($this->manager->can('access-dashboard-module', 'plans'));
        $this->assertFalse($this->manager->can('access-dashboard-module', 'managers'));
    }
}
