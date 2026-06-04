<?php

use App\Livewire\Admin\Events\EventBracketManager;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventParticipant;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the event bracket manager component', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EventBracketManager::class, ['event' => $event])
        ->assertStatus(200)
        ->assertSee('No Bracket Generated');
});

it('admin can generate a bracket', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create(['requires_check_in' => false]);

    // Create 4 participants
    for ($i = 0; $i < 4; $i++) {
        $user = User::factory()->create();
        EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
    }

    $this->actingAs($admin);

    Livewire::test(EventBracketManager::class, ['event' => $event])
        ->call('generateBracket');

    // 4 participants = 3 matches total (2 in round 1, 1 in round 2)
    expect($event->matches()->count())->toBe(3);
});

it('admin can publish a bracket', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EventBracketManager::class, ['event' => $event])
        ->call('publishBracket');

    Queue::assertPushed(\App\Jobs\NotifyBracketPublishedJob::class, function ($job) use ($event) {
        return $job->event->id === $event->id;
    });
});

it('admin can advance a match winner', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $participant1 = EventParticipant::factory()->create(['event_id' => $event->id]);
    $participant2 = EventParticipant::factory()->create(['event_id' => $event->id]);

    $match = EventMatch::factory()->create([
        'event_id' => $event->id,
        'round' => 1,
        'match_number' => 1,
        'participant1_id' => $participant1->id,
        'participant2_id' => $participant2->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(EventBracketManager::class, ['event' => $event])
        ->call('openAdvanceModal', $match->id)
        ->set('winnerId', $match->participant1_id)
        ->set('matchScore', '21-15')
        ->call('confirmAdvance')
        ->assertHasNoErrors();

    $match->refresh();
    expect($match->winner_id)->toBe($match->participant1_id);
    expect($match->score)->toBe('21-15');
    expect($match->status)->toBe('completed');
});
