<?php

use App\Events\CheckInProcessed;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

test('checkin webhook stays under five hundred milliseconds average for rapid sequential requests', function () {
    // Mock Redis for anti-passback rule
    Redis::shouldReceive('get')->andReturn(null);
    Redis::shouldReceive('set')->andReturn(true);

    $terminal = HikvisionTerminal::factory()->create([
        'api_token' => 'performance-terminal-token',
        'status' => 'offline',
    ]);

    Event::fake([CheckInProcessed::class]);

    $iterations = 20;
    $totalMilliseconds = 0.0;

    foreach (range(1, $iterations) as $index) {
        $startedAt = hrtime(true);

        $response = $this->withHeader('Authorization', 'Bearer performance-terminal-token')
            ->postJson('/api/checkin', [
                'card_uid' => sprintf('PERF-CARD-%03d', $index),
                'result' => 'authorized',
            ]);

        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;
        $totalMilliseconds += $elapsedMilliseconds;

        $response->assertOk();
    }

    $averageMilliseconds = $totalMilliseconds / $iterations;

    expect($averageMilliseconds)->toBeLessThan(500.0);
    expect(CheckInEvent::query()->count())->toBe($iterations);

    Event::assertDispatchedTimes(CheckInProcessed::class, $iterations);
});

test('checkin webhook keeps each sequential request under one second', function () {
    // Mock Redis for anti-passback rule
    Redis::shouldReceive('get')->andReturn(null);
    Redis::shouldReceive('set')->andReturn(true);

    HikvisionTerminal::factory()->create([
        'api_token' => 'performance-terminal-token-per-request',
        'status' => 'offline',
    ]);

    Event::fake([CheckInProcessed::class]);

    foreach (range(1, 10) as $index) {
        $startedAt = hrtime(true);

        $response = $this->withHeader('Authorization', 'Bearer performance-terminal-token-per-request')
            ->postJson('/api/checkin', [
                'card_uid' => sprintf('PERF-SINGLE-%03d', $index),
                'result' => 'authorized',
            ]);

        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        $response->assertOk();
        expect($elapsedMilliseconds)->toBeLessThan(1000.0);
    }

    Event::assertDispatchedTimes(CheckInProcessed::class, 10);
});
