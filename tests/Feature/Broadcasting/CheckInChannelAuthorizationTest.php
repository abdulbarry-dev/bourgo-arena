<?php

use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app-id');
    config()->set('broadcasting.connections.reverb.options.host', '127.0.0.1');
    config()->set('broadcasting.connections.reverb.options.port', 8080);
    config()->set('broadcasting.connections.reverb.options.scheme', 'http');
    config()->set('broadcasting.connections.reverb.options.useTLS', false);

    app(BroadcastManager::class)->setDefaultDriver('reverb');
    app(BroadcastManager::class)->forgetDrivers();

    require base_path('routes/channels.php');
});

function authenticateCheckinsChannel(User $user): array
{
    $request = Request::create('/broadcasting/auth', 'POST', [
        'channel_name' => 'private-checkins',
        'socket_id' => '1234.5678',
    ]);

    $request->setUserResolver(fn (): User => $user);

    return app(BroadcastManager::class)
        ->connection('reverb')
        ->auth($request);
}

test('admin can authenticate private checkins broadcast channel', function () {
    $admin = User::factory()->admin()->create();

    $payload = authenticateCheckinsChannel($admin);

    expect($payload)->toHaveKey('auth');
});

test('manager can authenticate private checkins broadcast channel', function () {
    $manager = User::factory()->manager()->create();

    $payload = authenticateCheckinsChannel($manager);

    expect($payload)->toHaveKey('auth');
});

test('member is forbidden from private checkins broadcast channel', function () {
    $member = User::factory()->member()->create();

    expect(fn () => authenticateCheckinsChannel($member))
        ->toThrow(AccessDeniedHttpException::class);
});
