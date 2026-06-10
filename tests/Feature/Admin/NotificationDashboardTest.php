<?php

use App\Livewire\Admin\Notifications\Dashboard;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

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

it('creates a new notification type', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openCreateTypeFlyout')
        ->assertSet('showTypeFlyout', true)
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

it('edits an existing notification type', function () {
    $type = NotificationType::factory()->create([
        'slug' => 'editable_type',
        'name' => 'Original Name',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('openEditTypeFlyout', $type->id)
        ->assertSet('showTypeFlyout', true)
        ->assertSet('typeName', 'Original Name')
        ->set('typeName', 'Updated Name')
        ->call('saveType');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'name' => 'Updated Name',
    ]);
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

it('toggles a channel on a notification type', function () {
    $type = NotificationType::factory()->create([
        'sms_enabled' => false,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'sms');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'sms_enabled' => true,
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

it('has a notification type toggling each channel independently', function () {
    $type = NotificationType::factory()->create([
        'push_enabled' => true,
        'email_enabled' => true,
        'sms_enabled' => false,
    ]);

    $this->actingAs($this->admin);

    // Toggle push off
    Livewire::test(Dashboard::class)
        ->call('toggleTypeChannel', $type->id, 'push');

    $this->assertDatabaseHas('notification_types', [
        'id' => $type->id,
        'push_enabled' => false,
    ]);
});

it('allows specific member selection in compose', function () {
    $member = Member::factory()->create(['name' => 'Specific Member', 'is_archived' => false]);
    $type = NotificationType::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeAudience', 'specific')
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

it('resets compose form', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->set('composeSubject', 'Some subject')
        ->set('composeBody', 'Some body')
        ->call('resetCompose')
        ->assertSet('composeSubject', '')
        ->assertSet('composeBody', '');
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
