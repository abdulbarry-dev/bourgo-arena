<?php

use App\Actions\Events\AdvanceMatchWinnerAction;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('advances a winner to the next match', function () {
    $event = Event::factory()->create();

    $participant1 = EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => User::factory()->create()->id]);
    $participant2 = EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => User::factory()->create()->id]);

    $nextMatch = EventMatch::factory()->create([
        'event_id' => $event->id,
        'round' => 2,
    ]);

    $currentMatch = EventMatch::factory()->create([
        'event_id' => $event->id,
        'round' => 1,
        'participant1_id' => $participant1->id,
        'participant2_id' => $participant2->id,
        'next_match_id' => $nextMatch->id,
    ]);

    $action = new AdvanceMatchWinnerAction();
    $action->execute($currentMatch, $participant1->id, '2-1');

    $currentMatch->refresh();
    $nextMatch->refresh();

    // Verify current match is completed
    expect($currentMatch->status)->toBe('completed');
    expect($currentMatch->winner_id)->toBe($participant1->id);
    expect($currentMatch->score)->toBe('2-1');

    // Verify winner was advanced
    expect($nextMatch->participant1_id)->toBe($participant1->id);
});

it('fails if the winner is not part of the match', function () {
    $event = Event::factory()->create();

    $participant1 = EventParticipant::factory()->create(['event_id' => $event->id]);
    $participant2 = EventParticipant::factory()->create(['event_id' => $event->id]);
    $randomParticipant = EventParticipant::factory()->create(['event_id' => $event->id]);

    $currentMatch = EventMatch::factory()->create([
        'event_id' => $event->id,
        'round' => 1,
        'participant1_id' => $participant1->id,
        'participant2_id' => $participant2->id,
    ]);

    $action = new AdvanceMatchWinnerAction();
    
    $action->execute($currentMatch, $randomParticipant->id);
})->throws(Exception::class, 'The selected winner is not part of this match.');
