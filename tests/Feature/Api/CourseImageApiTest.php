<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes multiple images in course list response', function () {
    Course::factory()->create([
        'status' => 'active',
        'images' => ['path1.jpg', 'path2.jpg'],
    ]);

    $response = $this->getJson('/api/v1/courses');

    $response->assertStatus(200)
        ->assertJsonFragment(['images' => [
            asset('storage/path1.jpg'),
            asset('storage/path2.jpg'),
        ]]);
});

it('includes multiple images in course detail response', function () {
    $course = Course::factory()->create([
        'status' => 'active',
        'images' => ['path1.jpg', 'path2.jpg'],
    ]);

    $response = $this->getJson("/api/v1/courses/{$course->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.images', [
            asset('storage/path1.jpg'),
            asset('storage/path2.jpg'),
        ]);
});
