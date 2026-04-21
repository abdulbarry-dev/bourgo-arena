<?php

use App\Models\HikvisionTerminal;
use App\Services\AntiPassbackRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('flags consecutive entry scans as suspicious', function () {
    $terminal = HikvisionTerminal::factory()->create(['terminal_type' => 'entry']);
    $cardUid = 'TESTCARD123';

    // Mock Cache
    Cache::shouldReceive('get')
        ->with("card_state:{$cardUid}")
        ->andReturn(json_encode([
            'direction' => 'IN',
            'last_event_at' => now()->subMinutes(5)->timestamp,
        ]));

    Cache::shouldReceive('put')
        ->once();

    $rule = app(AntiPassbackRule::class);
    $isSuspicious = $rule->isSuspicious($cardUid, 'entry');

    expect($isSuspicious)->toBeTrue();
});

it('does not flag entry after exit', function () {
    $exitTerminal = HikvisionTerminal::factory()->create(['terminal_type' => 'exit']);
    $cardUid = 'TESTCARD456';

    // Mock Cache
    Cache::shouldReceive('get')
        ->with("card_state:{$cardUid}")
        ->andReturn(json_encode([
            'direction' => 'OUT',
            'last_event_at' => now()->subMinutes(5)->timestamp,
        ]));

    Cache::shouldReceive('put')
        ->once();

    $rule = app(AntiPassbackRule::class);
    $isSuspicious = $rule->isSuspicious($cardUid, 'entry');

    expect($isSuspicious)->toBeFalse();
});
