<?php

use App\Livewire\Admin\Events\EventParticipants;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the event participants component', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EventParticipants::class, ['event' => $event])
        ->assertStatus(200)
        ->assertSee($event->name);
});

it('filters participants by search query', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $user1 = User::factory()->create(['name' => 'Alice Doe', 'email' => 'alice@example.com']);
    $user2 = User::factory()->create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user1->id]);
    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user2->id]);

    $this->actingAs($admin);

    Livewire::test(EventParticipants::class, ['event' => $event])
        ->set('search', 'Alice')
        ->assertSee('Alice Doe')
        ->assertDontSee('Bob Smith');
});

it('filters participants by status', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $user1 = User::factory()->create(['name' => 'Registered User']);
    $user2 = User::factory()->create(['name' => 'Canceled User']);

    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user1->id, 'status' => 'registered']);
    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user2->id, 'status' => 'canceled']);

    $this->actingAs($admin);

    Livewire::test(EventParticipants::class, ['event' => $event])
        ->set('statusFilter', 'canceled')
        ->assertSee('Canceled User')
        ->assertDontSee('Registered User');
});

it('filters participants by team', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $team1 = Team::factory()->create(['name' => 'Team Alpha']);
    $team2 = Team::factory()->create(['name' => 'Team Beta']);

    $user1 = User::factory()->create(['name' => 'Alpha Player']);
    $user2 = User::factory()->create(['name' => 'Beta Player']);

    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user1->id, 'team_id' => $team1->id]);
    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user2->id, 'team_id' => $team2->id]);

    $this->actingAs($admin);

    Livewire::test(EventParticipants::class, ['event' => $event])
        ->set('teamFilter', $team1->id)
        ->assertSee('Alpha Player')
        ->assertDontSee('Beta Player');
});
