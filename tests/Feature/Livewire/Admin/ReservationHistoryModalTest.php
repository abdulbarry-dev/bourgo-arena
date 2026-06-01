<?php

use App\Livewire\Admin\Reservations\ReservationManager;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('opens reconciliation modal and displays entries', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = Member::factory()->create();
    $activity = Activity::factory()->create(['category' => 'Padel']);
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id, 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'capacity' => 4, 'booked_count' => 1]);

    $reservation = ApiReservation::factory()->for($member)->forActivity($activity)->forSlot($slot)->create(['payment_status' => 'paid', 'status' => 'confirmed']);

    $payment = Payment::factory()->create(['member_id' => $member->id, 'reservation_id' => $reservation->id, 'status' => 'paid', 'amount' => 10.00]);

    PaymentReconciliation::query()->create(['payment_id' => $payment->id, 'admin_id' => $admin->id, 'type' => 'reconciled', 'amount' => null, 'metadata' => ['provider' => 'test']]);

    $this->actingAs($admin);

    Livewire::test(ReservationManager::class)
        ->call('openReservationDetail', $reservation->id)
        ->call('openHistoryModal', $payment->id)
        ->assertSet('historyPaymentId', $payment->id)
        ->assertSee('Reconciliation History')
        ->assertSee('Reconciled')
        ->assertSee($admin->name);
});
