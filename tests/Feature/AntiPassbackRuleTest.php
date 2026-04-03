<?php

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Services\AntiPassbackRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('flags consecutive entry scans as suspicious', function () {
    $terminal = HikvisionTerminal::factory()->create(['type' => 'entry']);
    $cardUid = 'TESTCARD123';

    // First scan creates an entry
    $event1 = CheckInEvent::factory()->create([
        'card_uid' => $cardUid,
        'terminal_id' => $terminal->id,
        'checked_in_at' => now()->subMinutes(5),
    ]);

    $rule = app(AntiPassbackRule::class);
    $isSuspicious = $rule->isSuspicious($cardUid, 'entry');

    expect($isSuspicious)->toBeTrue();
});

it('does not flag entry after exit', function () {
    $exitTerminal = HikvisionTerminal::factory()->create(['type' => 'exit']);
    $cardUid = 'TESTCARD456';

    $event1 = CheckInEvent::factory()->create([
        'card_uid' => $cardUid,
        'terminal_id' => $exitTerminal->id,
        'checked_in_at' => now()->subMinutes(5),
    ]);

    $rule = app(AntiPassbackRule::class);
    $isSuspicious = $rule->isSuspicious($cardUid, 'entry');

    expect($isSuspicious)->toBeFalse();
});
