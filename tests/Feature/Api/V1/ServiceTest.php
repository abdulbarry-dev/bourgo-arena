<?php

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list active services', function () {
    Service::factory()->create([
        'name' => 'Active Service',
        'status' => 'active',
        'image_url' => 'https://example.com/image.jpg',
    ]);

    Service::factory()->create([
        'name' => 'Inactive Service',
        'status' => 'inactive',
    ]);

    $response = $this->getJson(route('api.v1.services.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active Service')
        ->assertJsonPath('data.0.image_url', 'https://example.com/image.jpg');
});

it('can get specific service details', function () {
    $service = Service::factory()->create([
        'name' => 'Active Service',
        'status' => 'active',
        'image_url' => 'https://example.com/image.jpg',
    ]);

    $response = $this->getJson(route('api.v1.services.show', $service));

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Active Service')
        ->assertJsonPath('data.image_url', 'https://example.com/image.jpg');
});
