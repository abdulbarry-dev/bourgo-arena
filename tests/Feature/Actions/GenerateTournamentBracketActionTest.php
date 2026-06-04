<?php

use App\Actions\Events\GenerateTournamentBracketAction;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a bracket for exactly 4 participants', function () {
    $event = Event::factory()->create();

    // Create 4 participants
    for ($i = 0; $i < 4; $i++) {
        $user = User::factory()->create();
        EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
    }

    $action = new GenerateTournamentBracketAction();
    $action->execute($event);

    // 4 participants -> 2 matches in round 1, 1 match in round 2
    expect($event->matches()->count())->toBe(3);
    
    $round1Matches = $event->matches()->where('round', 1)->get();
    expect($round1Matches->count())->toBe(2);

    $round2Matches = $event->matches()->where('round', 2)->get();
    expect($round2Matches->count())->toBe(1);

    // Both round 1 matches should have the round 2 match as their next match
    $finalMatchId = $round2Matches->first()->id;
    foreach ($round1Matches as $match) {
        expect($match->next_match_id)->toBe($finalMatchId);
        expect($match->participant1_id)->not->toBeNull();
        expect($match->participant2_id)->not->toBeNull();
    }
});

it('generates a bracket for 3 participants and assigns 1 bye', function () {
    $event = Event::factory()->create();

    // Create 3 participants
    for ($i = 0; $i < 3; $i++) {
        $user = User::factory()->create();
        EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
    }

    $action = new GenerateTournamentBracketAction();
    $action->execute($event);

    // Next power of 2 is 4, so it builds a bracket of 4.
    // That means 3 total matches.
    expect($event->matches()->count())->toBe(3);

    $round1Matches = $event->matches()->where('round', 1)->get();
    expect($round1Matches->count())->toBe(2);

    // One match in round 1 should have a participant and a bye (null participant2)
    // The other should have both participants
    $byes = 0;
    foreach ($round1Matches as $match) {
        if ($match->participant2_id === null) {
            $byes++;
            // If it's a bye, the match should be automatically completed
            expect($match->status)->toBe('completed');
            expect($match->winner_id)->toBe($match->participant1_id);
        }
    }
    
    expect($byes)->toBe(1);
});

it('deletes old matches when regenerating a bracket', function () {
    $event = Event::factory()->create();

    $user = User::factory()->create();
    EventParticipant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);

    $action = new GenerateTournamentBracketAction();
    
    // First generation (will just make 1 match since it needs at least power of 2=2)
    $action->execute($event);
    $firstMatchId = $event->matches()->first()->id;

    // Regenerate
    $action->execute($event);

    expect($event->matches()->where('id', $firstMatchId)->exists())->toBeFalse();
    expect($event->matches()->count())->toBe(1);
});
