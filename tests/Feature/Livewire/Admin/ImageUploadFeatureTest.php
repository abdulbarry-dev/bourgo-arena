<?php

use App\Livewire\Admin\Activities\ActivityManager;
use App\Livewire\Admin\Courses\CourseManager;
use App\Livewire\Admin\Events\EventManager;
use App\Livewire\Admin\Services\ServiceManager;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

dataset('managers', [
    ActivityManager::class,
    CourseManager::class,
    EventManager::class,
    ServiceManager::class,
]);

it('processes valid image uploads and moves them to newImages', function ($component) {
    Storage::fake('public');
    
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    $file1 = UploadedFile::fake()->image('photo1.jpg');
    
    Livewire::test($component)
        ->set('uploadQueue', [$file1])
        ->call('processUploadQueue')
        ->assertHasNoErrors()
        ->assertSet('uploadQueue', [])
        ->assertCount('newImages', 1);
})->with('managers');

it('validates that uploaded files are images', function ($component) {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
    
    Livewire::test($component)
        ->set('uploadQueue', [$file])
        ->call('processUploadQueue')
        ->assertHasErrors(['uploadQueue.0' => 'image']);
})->with('managers');

it('validates that uploaded images do not exceed 2MB', function ($component) {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    // Create a 3MB fake image
    $file = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg');
    
    Livewire::test($component)
        ->set('uploadQueue', [$file])
        ->call('processUploadQueue')
        ->assertHasErrors(['uploadQueue.0' => 'max']);
})->with('managers');

it('limits the total number of images to 3 and dispatches a toast', function ($component) {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    $files = [
        UploadedFile::fake()->image('1.jpg'),
        UploadedFile::fake()->image('2.jpg'),
        UploadedFile::fake()->image('3.jpg'),
        UploadedFile::fake()->image('4.jpg'),
    ];
    
    Livewire::test($component)
        ->set('uploadQueue', $files)
        ->call('processUploadQueue')
        ->assertCount('newImages', 3) // Only 3 should be accepted
        ->assertDispatched('toast', message: __('Maximum of 3 images allowed.'), type: 'danger');
})->with('managers');

it('can confirm and delete a new pending image', function ($component) {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    $file = UploadedFile::fake()->image('photo.jpg');
    
    Livewire::test($component)
        ->set('newImages', [$file])
        ->call('confirmImageDeletion', 0, true)
        ->assertSet('imageToDeleteIndex', 0)
        ->assertSet('isNewImageDeletion', true)
        ->call('executeImageDeletion')
        ->assertCount('newImages', 0);
})->with('managers');

it('can confirm and delete an existing stored image', function ($component) {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    Livewire::test($component)
        ->set('images', ['path/to/old/image.jpg'])
        ->call('confirmImageDeletion', 0, false)
        ->assertSet('imageToDeleteIndex', 0)
        ->assertSet('isNewImageDeletion', false)
        ->call('executeImageDeletion')
        ->assertCount('images', 0);
})->with('managers');

