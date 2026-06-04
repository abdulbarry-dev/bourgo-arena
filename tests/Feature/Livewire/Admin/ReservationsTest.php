<?php

use App\Livewire\Admin\Reservations\ReservationManager;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use App\Services\PaymentService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('paginates reservations five per page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    ApiReservation::factory()->count(6)->create();

    $this->actingAs($admin);

    $component = Livewire::test(ReservationManager::class);

    expect($component->instance()->reservations->perPage())->toBe(5)
        ->and($component->instance()->reservations->count())->toBe(5)
        ->and($component->instance()->reservations->total())->toBe(6);
});

it('displays member avatar in the reservations table', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = Member::factory()->create([
        'name' => 'Avatar Member',
        'avatar' => 'members/avatars/table.jpg',
    ]);

    ApiReservation::factory()->for($member, 'member')->create();

    $this->actingAs($admin);

    Livewire::test(ReservationManager::class)
        ->assertSee('Avatar Member')
        ->assertSee(asset('storage/members/avatars/table.jpg'), false);
});

it('renders the reservations manager page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertSee('h-dvh overflow-hidden', false)
        ->assertSee('Reservations Manager')
        ->assertSee('New Reservation');
});

it('allows an admin to create a reservation for a client', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = Member::factory()->create([
        'name' => 'Client One',
        'email' => 'client.one@example.com',
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    $activity = Activity::factory()->create([
        'title' => 'Court Alpha',
        'base_price' => 80,
        'is_active' => true,
    ]);
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'starts_at' => '12:00:00',
        'ends_at' => '13:00:00',
        'capacity' => 6,
        'booked_count' => 0,
        'is_available' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ReservationManager::class)
        ->call('openCreateModal')
        ->set('memberSearch', 'Client One')
        ->set('activitySearch', 'Court Alpha')
        ->set('createMemberId', $member->id)
        ->set('createActivityId', $activity->id)
        ->set('createDate', now()->addDay()->toDateString())
        ->set('createActivitySlotId', $slot->id)
        ->call('createReservation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('api_reservations', [
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'status' => 'confirmed',
        'payment_status' => 'pending',
    ]);

    $this->assertEquals(1, $slot->fresh()->booked_count);
});

it('shows reservation member and payment history details', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    $activity = Activity::factory()->create(['title' => 'Stade Padel 1']);
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
        'capacity' => 4,
        'booked_count' => 1,
    ]);

    $reservation = ApiReservation::factory()
        ->for($member)
        ->forActivity($activity)
        ->forSlot($slot)
        ->create([
            'date' => now()->addDay()->toDateString(),
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'reservation_id' => $reservation->id,
        'status' => 'paid',
        'amount' => $reservation->price,
        'payment_reference' => 'PAY-RES-001',
        'verified_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ReservationManager::class)
        ->assertSee('Stade Padel 1')
        ->assertSee($member->name)
        ->call('openReservationDetail', $reservation->id)
        ->assertSet('selectedReservationId', $reservation->id)
        ->assertSee('PAY-RES-001')
        ->assertSee('Full Profile')
        ->assertSee('Loyalty Points');
});

it('can verify payments from the detail flyout', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = Member::factory()->create();
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id, 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'capacity' => 4, 'booked_count' => 1]);

    $reservation = ApiReservation::factory()->for($member)->forActivity($activity)->forSlot($slot)->create(['date' => now()->addDay()->toDateString(), 'payment_status' => 'pending', 'status' => 'confirmed']);

    $payment = Payment::factory()->create(['member_id' => $member->id, 'reservation_id' => $reservation->id, 'status' => 'pending', 'amount' => $reservation->price, 'payment_reference' => 'PAY-VERIFY-001']);

    // Mock PaymentService verify to avoid external calls and update the payment record
    $serviceMock = Mockery::mock(PaymentService::class);
    $serviceMock->shouldReceive('verify')->andReturnUsing(function ($p, $tx = null) {
        $p->update(['status' => 'paid', 'verified_at' => now()]);

        return ['success' => true, 'status' => 'paid'];
    });
    $this->app->instance(PaymentService::class, $serviceMock);

    $this->actingAs($admin);

    Livewire::test(ReservationManager::class)
        ->call('openReservationDetail', $reservation->id)
        ->call('verifyPayment', $payment->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    $this->assertDatabaseHas('api_reservations', ['id' => $reservation->id, 'payment_status' => 'paid']);
});
