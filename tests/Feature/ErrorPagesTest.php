<?php

use Illuminate\Support\Facades\Route;

test('custom 404 page is rendered', function () {
    $response = $this->withHeader('referer', '/admin')->get('/missing-dashboard-page');

    $response->assertNotFound();
    $response->assertSee('Page Not Found');
    $response->assertSee('Go back');
    $response->assertSee('href="'.url('/admin').'"', false);
});

test('custom 401 page is rendered', function () {
    Route::get('/test-error-401', fn () => abort(401));

    $response = $this->get('/test-error-401');

    $response->assertUnauthorized();
    $response->assertSee('Unauthorized');
    $response->assertSee('Go back');
});

test('custom 403 page is rendered', function () {
    Route::get('/test-error-403', fn () => abort(403));

    $response = $this->get('/test-error-403');

    $response->assertForbidden();
    $response->assertSee('Forbidden');
    $response->assertSee('Go back');
});

test('custom 500 page is rendered', function () {
    Route::get('/test-error-500', fn () => abort(500));

    $response = $this->get('/test-error-500');

    $response->assertStatus(500);
    $response->assertSee('Server Error');
    $response->assertSee('Go back');
});
