<?php

use App\Models\Member;
use App\Models\User;

test('members dashboard initial load stays under two seconds with one thousand members', function () {
    $manager = User::factory()->manager()->create();
    Member::factory()->count(1000)->create();

    $startedAt = hrtime(true);

    $response = $this->actingAs($manager)->get(route('admin.members'));

    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

    $response->assertOk();
    $response->assertSee('Member Management');

    expect($elapsedMilliseconds)->toBeLessThan(2000.0);
});
