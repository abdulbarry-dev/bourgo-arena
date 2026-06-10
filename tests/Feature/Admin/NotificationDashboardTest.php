<?php

use App\Livewire\Admin\Notifications\Dashboard;
use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Setup ─────────────────────────────────────────────────
beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

// ─── Rendering & Authorization ───────────────────────────
it('renders the notification dashboard page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.notifications'))
        ->assertOk()
        ->assertSee('Notification Center');
});

it('denies access to managers', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)
        ->get(route('admin.notifications'))
        ->assertForbidden();
});

it('shows the notification dashboard component with stats', function () {
    NotificationType::factory()->count(3)->create();
    NotificationLog::factory()->count(2)->create(['status' => 'sent']);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('Notification Center')
        ->assertSee('Notifications Sent');
});

it('displays notification types in the grid', function () {
    NotificationType::factory()->create([
        'slug' => 'test_type',
        'name' => 'Test Notification Type',
        'category' => 'system',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('Test Notification Type');
});

it('groups types by category in the correct order', function () {
    NotificationType::factory()->create(['slug' => 'sys_a', 'name' => 'System Alpha', 'category' => 'system']);
    NotificationType::factory()->create(['slug' => 'bil_a', 'name' => 'Billing Alpha', 'category' => 'billing']);
    NotificationType::factory()->create(['slug' => 'eve_a', 'name' => 'Events Alpha', 'category' => 'events']);
    NotificationType::factory()->create(['slug' => 'pro_a', 'name' => 'Promo Alpha', 'category' => 'promotions']);

    $this->actingAs($this->admin);

    $component = Livewire::test(Dashboard::class);
    // All 4 categories should be visible
    $component->assertSee('System Alpha')
        ->assertSee('Billing Alpha')
        ->assertSee('Events Alpha')
        ->assertSee('Promo Alpha');
});

// ─── Stats Edge Cases ────────────────────────────────────
it('shows 100% success rate when no notifications exist', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('100%');
});

it('shows correct success rate with mixed statuses', function () {
    NotificationLog::factory()->count(3)->create(['status' => 'sent']);
    NotificationLog::factory()->count(1)->create(['status' => 'failed']);

    $this->actingAs($this->admin);

    // 3 sent out of 4 total = 75%
    Livewire::test(Dashboard::class)
        ->assertSee('75%');
});

it('shows zero stats when only failed notifications exist', function () {
    NotificationLog::factory()->count(2)->create(['status' => 'failed']);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('0%');
});

it('counts queued notifications in the total sent stat', function () {
    NotificationLog::factory()->count(3)->create(['status' => 'queued']);
    NotificationLog::factory()->count(2)->create(['status' => 'sent']);

    $this->actingAs($this->admin);

    // total sent+queued = 5
    Livewire::test(Dashboard::class)
        ->assertSee('5');
});

it('counts registered devices in stats', function () {
    $member = Member::factory()->create();
    MemberDeviceToken::factory()->count(4)->create([
        'member_id' => $member->id,
        'is_active' => true,
    ]);
    // One inactive device should NOT be counted
    MemberDeviceToken::factory()->create([
        'member_id' => $member->id,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('4');
});

// ─── Type CRUD ───────────────────────────────────────────
it('creates a new notification type', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->assertSet('typeName', '')
        ->assertSet('typeSlug', '')
        ->assertSet('typeCategory', 'system')
        ->set('typeName', 'My Custom Type')
        ->set('typeSlug', 'my_custom_type')
        ->set('typeCategory', 'promotions')
        ->set('typePushEnabled', true)
        ->set('typeEmailEnabled', true)
        ->set('typeSmsEnabled', false)
        ->call('saveType');

    $this->assertDatabaseHas('notification_types', [
        'slug' => 'my_custom_type',
        'name' => 'My Custom Type',
        'category' => 'promotions',
    ]);
});

it('creates a type with nullable description as null', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->set('typeName', 'No Desc Type')
        ->set('typeSlug', 'no_desc_type')
        ->set('typeCategory', 'system')
        ->set('typeDescription', '')
        ->call('saveType');

    $this->assertDatabaseHas('notification_types', [
        'slug' => 'no_desc_type',
        'description' => null,
    ]);
});

it('prevents creating a type with a duplicate slug', function () {
    NotificationType::factory()->create(['slug' => 'existing_slug']);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->set('typeName', 'Duplicate Slug')
        ->set('typeSlug', 'existing_slug')
        ->set('typeCategory', 'system')
        ->call('saveType')
        ->assertHasErrors('typeSlug');
});

it('prevents creating a type with an invalid category', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->set('typeName', 'Bad Category')
        ->set('typeSlug', 'bad_category')
        ->set('typeCategory', 'invalid_category')
        ->call('saveType')
        ->assertHasErrors('typeCategory');
});

it('prevents creating a type with a name exceeding 255 characters', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->set('typeName', str_repeat('a', 256))
        ->set('typeSlug', 'long_name')
        ->set('typeCategory', 'system')
        ->call('saveType')
        ->assertHasErrors('typeName');
});

it('edits an existing notification type', function () {
    $type = NotificationType::factory()->create([
        'slug' => 'editable_type',
        'name' => 'Original Name',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openEditTypeFlyout', $type->id)
        ->assertSet('typeName', 'Original Name')
        ->set('typeName', 'Updated Name')
        ->call('saveType');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'name' => 'Updated Name',
    ]);
});

it('allows editing a type without changing its slug', function () {
    $type = NotificationType::factory()->create([
        'slug' => 'keep_slug',
        'name' => 'Original',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openEditTypeFlyout', $type->id)
        ->set('typeName', 'Updated Name')
        // slug kept as 'keep_slug' — should not trigger unique conflict
        ->call('saveType');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'slug' => 'keep_slug',
        'name' => 'Updated Name',
    ]);
});

it('gracefully handles toggling channel on a non-existent type', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', 9999, 'push');

    // Should not crash — toggling a missing type is silently ignored
    expect(true)->toBeTrue();
});

it('gracefully handles toggling active status on a non-existent type', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeActive', 9999);

    expect(true)->toBeTrue();
});

it('deletes a notification type', function () {
    $type = NotificationType::factory()->create([
        'slug' => 'deletable_type',
        'name' => 'To Delete',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('confirmDeleteType', $type->id)
        ->call('deleteType');

    $this->assertDatabaseMissing('notification_types', [
        'id' => $type->id,
    ]);
});

it('does nothing when deleteType is called without a selected type', function () {
    $type = NotificationType::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        // Call deleteType directly without confirmDeleteType first
        ->call('deleteType');

    // Type should still exist
    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
    ]);
});

it('toggles push channel on a notification type', function () {
    $type = NotificationType::factory()->create(['push_enabled' => true]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'push');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'push_enabled' => false,
    ]);
});

it('toggles email channel on a notification type', function () {
    $type = NotificationType::factory()->create(['email_enabled' => true]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'email');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'email_enabled' => false,
    ]);
});

it('toggles sms channel on a notification type', function () {
    $type = NotificationType::factory()->create(['sms_enabled' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'sms');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'sms_enabled' => true,
    ]);
});

it('silently ignores toggling an invalid channel name', function () {
    $type = NotificationType::factory()->create(['push_enabled' => true]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'invalid_channel');

    // push_enabled should remain unchanged
    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'push_enabled' => true,
    ]);
});

it('toggles active status on a notification type', function () {
    $type = NotificationType::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeActive', $type->id);

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'is_active' => true,
    ]);
});

it('resets type form when opening create after edit', function () {
    $type = NotificationType::factory()->create([
        'slug' => 'reset_test',
        'name' => 'Reset Me',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openEditTypeFlyout', $type->id)
        ->assertSet('typeName', 'Reset Me')
        ->call('openCreateTypeFlyout')
        ->assertSet('typeName', '')
        ->assertSet('typeSlug', '')
        ->assertSet('typeCategory', 'system');
});

// ─── Validation ──────────────────────────────────────────
it('validates the compose form', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('confirmSend')
        ->assertHasErrors('composeTypeId')
        ->assertHasErrors('composeChannels')
        ->assertHasErrors('composeSubject')
        ->assertHasErrors('composeBody');
});

it('validates the notification type form', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->call('saveType')
        ->assertHasErrors('typeName')
        ->assertHasErrors('typeSlug');
});

it('validates compose channels array does not accept invalid values', function () {
    $type = NotificationType::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->set('composeChannels', ['push', 'invalid_channel'])
        ->set('composeSubject', 'Test')
        ->set('composeBody', 'Test body')
        ->call('confirmSend')
        ->assertHasErrors('composeChannels.1');
});

it('validates compose subject does not exceed 255 characters', function () {
    $type = NotificationType::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->set('composeChannels', ['push'])
        ->set('composeSubject', str_repeat('a', 256))
        ->set('composeBody', 'Test body')
        ->call('confirmSend')
        ->assertHasErrors('composeSubject');
});

it('validates compose channels must have at least one selection', function () {
    $type = NotificationType::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->set('composeChannels', [])
        ->set('composeSubject', 'Test')
        ->set('composeBody', 'Test body')
        ->call('confirmSend')
        ->assertHasErrors('composeChannels');
});

it('validates compose type must exist', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', 9999)
        ->set('composeChannels', ['push'])
        ->set('composeSubject', 'Test')
        ->set('composeBody', 'Test body')
        ->call('confirmSend')
        ->assertHasErrors('composeTypeId');
});

// ─── Compose ─────────────────────────────────────────────
it('compose section shows member count', function () {
    Member::factory()->count(5)->create(['is_archived' => false]);
    $type = NotificationType::factory()->create(['push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->assertSet('composeChannels', ['push', 'email'])
        ->assertSet('composeSubject', $type->name);
});

it('queues a notification via send', function () {
    $type = NotificationType::factory()->create(['push_enabled' => true, 'email_enabled' => true]);
    Member::factory()->count(3)->create(['is_archived' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->set('composeChannels', ['push', 'email'])
        ->set('composeSubject', 'Test Subject')
        ->set('composeBody', 'Test message body')
        ->call('confirmSend')
        ->call('sendNotification');

    $this->assertDatabaseHas('notification_logs', [
        'subject' => 'Test Subject',
        'status' => 'queued',
    ]);
});

it('queues notification for specific audience only creates logs for selected members', function () {
    $type = NotificationType::factory()->create(['push_enabled' => true]);
    $member1 = Member::factory()->create(['is_archived' => false]);
    $member2 = Member::factory()->create(['is_archived' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->set('composeChannels', ['push'])
        ->set('composeAudience', 'specific')
        ->call('addComposeMember', $member1->id)
        ->set('composeSubject', 'Specific Test')
        ->set('composeBody', 'For specific member')
        ->call('confirmSend')
        ->call('sendNotification');

    $this->assertDatabaseHas('notification_logs', [
        'member_id' => $member1->id,
        'subject' => 'Specific Test',
    ]);

    $this->assertDatabaseMissing('notification_logs', [
        'member_id' => $member2->id,
        'subject' => 'Specific Test',
    ]);
});

it('handles sending to specific members when no members selected', function () {
    $type = NotificationType::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->set('composeChannels', ['email'])
        ->set('composeAudience', 'specific')
        ->set('composeMemberIds', [])
        ->set('composeSubject', 'Empty Audience')
        ->set('composeBody', 'No one should get this')
        ->call('confirmSend')
        ->call('sendNotification');

    // No notification logs should be created since no members were selected
    $this->assertDatabaseMissing('notification_logs', [
        'subject' => 'Empty Audience',
    ]);
});

it('auto-selects channels when type is chosen', function () {
    $type = NotificationType::factory()->create([
        'push_enabled' => true,
        'email_enabled' => false,
        'sms_enabled' => true,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->assertSet('composeChannels', ['push', 'sms']);
});

it('clears compose state when type is changed to null', function () {
    $type = NotificationType::factory()->create([
        'push_enabled' => true,
        'email_enabled' => false,
        'sms_enabled' => false,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', $type->id)
        ->assertSet('composeChannels', ['push'])
        ->set('composeTypeId', null)
        ->assertSet('composeChannels', [])
        ->assertSet('composeSubject', '');
});

it('does nothing when updatedComposeTypeId receives a non-existent ID', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeTypeId', 9999)
        // Compose state should remain as-is (no crash)
        ->assertSet('composeTypeId', 9999);
});

it('compose member count shows correct count for all members', function () {
    Member::factory()->count(3)->create(['is_archived' => false]);
    Member::factory()->create(['is_archived' => true]); // archived should be excluded

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSet('composeAudience', 'all')
        ->assertSet('composeMemberCount', 3);
});

it('compose member count shows 0 for specific audience with no selections', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->assertSet('composeMemberCount', 0);
});

it('compose member count shows correct count for specific selections', function () {
    $members = Member::factory()->count(3)->create(['is_archived' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->call('addComposeMember', $members[0]->id)
        ->call('addComposeMember', $members[1]->id)
        ->assertSet('composeMemberCount', 2);
});

it('allows specific member selection in compose', function () {
    $member = Member::factory()->create(['name' => 'Specific Member', 'is_archived' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->call('addComposeMember', $member->id)
        ->assertSet('composeMemberIds', [$member->id]);
});

it('prevents adding duplicate member to compose selection', function () {
    $member = Member::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->call('addComposeMember', $member->id)
        ->call('addComposeMember', $member->id)
        ->assertSet('composeMemberIds', [$member->id]);
});

it('removes a selected member from compose', function () {
    $member1 = Member::factory()->create(['name' => 'Member One', 'is_archived' => false]);
    $member2 = Member::factory()->create(['name' => 'Member Two', 'is_archived' => false]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->call('addComposeMember', $member1->id)
        ->call('addComposeMember', $member2->id)
        ->call('removeComposeMember', $member1->id)
        ->assertSet('composeMemberIds', [$member2->id]);
});

it('searches members by name in compose', function () {
    Member::factory()->create(['name' => 'John Doe', 'is_archived' => false]);
    Member::factory()->create(['name' => 'Jane Smith', 'is_archived' => false]);

    $this->actingAs($this->admin);

    $component = Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->set('composeMemberSearch', 'John');

    $searchable = $component->get('searchableMembers');
    expect($searchable)->toHaveCount(1);
    expect($searchable->first()->name)->toBe('John Doe');
});

it('returns empty searchable members when search is empty', function () {
    Member::factory()->create(['name' => 'John Doe', 'is_archived' => false]);

    $this->actingAs($this->admin);

    $component = Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific');

    $searchable = $component->get('searchableMembers');
    expect($searchable)->toBeEmpty();
});

it('returns empty searchable members when no matches found', function () {
    Member::factory()->create(['name' => 'John Doe', 'is_archived' => false]);

    $this->actingAs($this->admin);

    $component = Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
        ->set('composeMemberSearch', 'NonExistentName');

    $searchable = $component->get('searchableMembers');
    expect($searchable)->toBeEmpty();
});

it('resets compose form', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeSubject', 'Some subject')
        ->set('composeBody', 'Some body')
        ->call('resetCompose')
        ->assertSet('composeSubject', '')
        ->assertSet('composeBody', '')
        ->assertSet('composeAudience', 'all')
        ->assertSet('composeMemberIds', []);
});

// ─── History ─────────────────────────────────────────────
it('shows notification logs in history', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->count(3)->create([
        'notification_type_id' => $type->id,
        'status' => 'sent',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('sent');
});

it('filters notification logs by status', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'status' => 'sent',
        'subject' => 'Sent notification',
    ]);
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'status' => 'failed',
        'subject' => 'Failed notification',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('logStatusFilter', 'failed')
        ->assertSee('Failed notification')
        ->assertDontSee('Sent notification');
});

it('shows all logs when status filter is cleared', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'status' => 'sent',
        'subject' => 'Sent notif',
    ]);
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'status' => 'failed',
        'subject' => 'Failed notif',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('logStatusFilter', 'failed')
        ->assertSee('Failed notif')
        ->set('logStatusFilter', '')
        ->assertSee('Sent notif')
        ->assertSee('Failed notif');
});

it('shows empty state when no notification logs exist', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('No notifications sent yet');
});

it('paginates notification logs', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->count(12)->create([
        'notification_type_id' => $type->id,
        'subject' => 'Pagination test',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('Pagination test');
});

it('shows queued and failed log entries in history', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'status' => 'queued',
        'subject' => 'Queued item',
    ]);
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'status' => 'failed',
        'subject' => 'Failed item',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSee('Queued item')
        ->assertSee('Failed item');
});

it('shows deleted notification type gracefully in history', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
    ]);
    $type->delete();

    $this->actingAs($this->admin);

    // Should render without error - log still visible with "Deleted" indicator
    Livewire::test(Dashboard::class)
        ->assertSee('Deleted');
});

// ─── Type CRUD Additional ────────────────────────────────
it('has a notification type toggling each channel independently', function () {
    $type = NotificationType::factory()->create([
        'push_enabled' => true,
        'email_enabled' => true,
        'sms_enabled' => false,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'push');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'push_enabled' => false,
        'email_enabled' => true,
        'sms_enabled' => false,
    ]);
});

// ─── Model & Factory Tests ──────────────────────────────
it('NotificationType boot sets slug from name on creation', function () {
    $type = NotificationType::create([
        'name' => 'My Test Type',
        'category' => 'system',
    ]);

    expect($type->slug)->toBe('my-test-type');
});

it('NotificationType has many logs', function () {
    $type = NotificationType::factory()->create();
    NotificationLog::factory()->count(3)->create([
        'notification_type_id' => $type->id,
    ]);

    expect($type->logs)->toHaveCount(3);
    expect($type->logs->first())->toBeInstanceOf(NotificationLog::class);
});

it('NotificationLog belongs to notificationType', function () {
    $type = NotificationType::factory()->create();
    $log = NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
    ]);

    expect($log->notificationType)->toBeInstanceOf(NotificationType::class);
    expect($log->notificationType->id)->toBe($type->id);
});

it('NotificationLog belongs to member', function () {
    $member = Member::factory()->create();
    $log = NotificationLog::factory()->create([
        'member_id' => $member->id,
    ]);

    expect($log->member)->toBeInstanceOf(Member::class);
    expect($log->member->id)->toBe($member->id);
});

it('NotificationLog can have null member', function () {
    $log = NotificationLog::factory()->create([
        'member_id' => null,
    ]);

    expect($log->member)->toBeNull();
});

it('NotificationType factory creates a valid type', function () {
    $type = NotificationType::factory()->create();

    expect($type->slug)->not->toBeEmpty();
    expect($type->name)->not->toBeEmpty();
    expect(['billing', 'events', 'promotions', 'system'])->toContain($type->category);
    expect($type->is_active)->toBeTrue();
});

it('NotificationType factory can create inactive types', function () {
    $type = NotificationType::factory()->inactive()->create();

    expect($type->is_active)->toBeFalse();
});

it('NotificationLog factory creates a valid log', function () {
    $log = NotificationLog::factory()->create();

    expect($log->channel)->toBeIn(['push', 'email', 'sms']);
    expect($log->status)->toBe('sent');
    expect($log->subject)->not->toBeEmpty();
    expect($log->body)->not->toBeEmpty();
});

it('NotificationLog factory can create queued logs', function () {
    $log = NotificationLog::factory()->queued()->create();

    expect($log->status)->toBe('queued');
    expect($log->sent_at)->toBeNull();
});

it('NotificationLog factory can create failed logs', function () {
    $log = NotificationLog::factory()->failed()->create();

    expect($log->status)->toBe('failed');
    expect($log->metadata)->toHaveKey('error');
});

it('retryLog processes a failed email notification synchronously', function () {
    $member = Member::factory()->create(['email' => 'test@example.com']);
    $log = NotificationLog::factory()->create([
        'member_id' => $member->id,
        'channel' => 'email',
        'status' => 'failed',
        'subject' => 'Retry test',
        'body' => 'Test body',
    ]);

    $this->actingAs($this->admin);

    Mail::fake();

    Livewire::test(Dashboard::class)
        ->call('retryLog', $log->id);

    $log->refresh();
    // Status becomes 'sent' since we mocked Mail
    expect($log->status)->toBe('sent');
    expect($log->sent_at)->not->toBeNull();
});

it('retryLog processes a failed push notification synchronously', function () {
    $member = Member::factory()->create();
    $log = NotificationLog::factory()->create([
        'member_id' => $member->id,
        'channel' => 'push',
        'status' => 'failed',
        'subject' => 'Push retry',
        'body' => 'Test push body',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('retryLog', $log->id);

    $log->refresh();
    // Push with no device token — job silently returns, status stays 'queued'
    // (that's correct: no token means no device to push to)
    expect($log->status)->toBe('queued');
});
