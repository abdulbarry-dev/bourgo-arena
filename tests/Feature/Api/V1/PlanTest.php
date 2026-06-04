<?php

use App\Models\Plan;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list available plans with embedded service image', function () {
    $service = Service::factory()->create([
        'status' => 'active',
        'image_url' => 'https://example.com/service.jpg',
    ]);

    Plan::factory()->create([
        'name' => 'Premium Plan',
        'service_id' => $service->id,
        'is_archived' => false,
    ]);

    Plan::factory()->create([
        'name' => 'Archived Plan',
        'service_id' => $service->id,
        'is_archived' => true,
    ]);

    $response = $this->getJson(route('api.v1.plans.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Premium Plan')
        ->assertJsonPath('data.0.service.image_url', 'https://example.com/service.jpg');
});

it('can get specific plan details', function () {
    $service = Service::factory()->create([
        'status' => 'active',
        'image_url' => 'https://example.com/service.jpg',
    ]);

    $plan = Plan::factory()->create([
        'name' => 'Premium Plan',
        'service_id' => $service->id,
        'is_archived' => false,
    ]);

    $response = $this->getJson(route('api.v1.plans.show', $plan));

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Premium Plan')
        ->assertJsonPath('data.service.image_url', 'https://example.com/service.jpg');
});
