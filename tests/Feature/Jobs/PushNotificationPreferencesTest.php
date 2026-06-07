<?php

use App\Jobs\SendCourseCancelledPush;
use App\Jobs\SendMemberWelcomePush;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Member;
use App\Notifications\LoyaltyPointsUpdatedNotification;
use App\Services\Members\PushNotificationService;

use function Pest\Laravel\mock;

test('SendCourseCancelledPush respects preferences', function () {
    $course = Course::factory()->create();
    $session = CourseSession::factory()->create(['course_id' => $course->id]);

    // Member 1: enabled
    $member1 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => true, 'courses' => true]],
    ]);
    $member1->deviceTokens()->create(['token' => 'token1', 'is_active' => true, 'device_type' => 'ios']);
    Booking::create(['member_id' => $member1->id, 'course_session_id' => $session->id, 'date' => now()->toDateString(), 'status' => 'confirmed']);

    // Member 2: push globally disabled
    $member2 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => false, 'courses' => true]],
    ]);
    $member2->deviceTokens()->create(['token' => 'token2', 'is_active' => true, 'device_type' => 'ios']);
    Booking::create(['member_id' => $member2->id, 'course_session_id' => $session->id, 'date' => now()->toDateString(), 'status' => 'confirmed']);

    // Member 3: courses disabled
    $member3 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => true, 'courses' => false]],
    ]);
    $member3->deviceTokens()->create(['token' => 'token3', 'is_active' => true, 'device_type' => 'ios']);
    Booking::create(['member_id' => $member3->id, 'course_session_id' => $session->id, 'date' => now()->toDateString(), 'status' => 'confirmed']);

    $mock = mock(PushNotificationService::class);
    $mock->shouldReceive('send')
        ->once()
        ->with(
            ['token1'],
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        );

    $job = new SendCourseCancelledPush($session->id, now()->toDateString());
    $job->handle($mock);
});

test('SendMemberWelcomePush respects preferences', function () {
    // Member 1: enabled
    $member1 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => true, 'account_updates' => true]],
    ]);
    $member1->deviceTokens()->create(['token' => 'token1', 'is_active' => true, 'device_type' => 'ios']);

    $mock = mock(PushNotificationService::class);
    $mock->shouldReceive('send')->once();

    $job = new SendMemberWelcomePush($member1->id);
    $job->handle($mock);

    // Member 2: disabled
    $member2 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => true, 'account_updates' => false]],
    ]);
    $member2->deviceTokens()->create(['token' => 'token2', 'is_active' => true, 'device_type' => 'ios']);

    $mock2 = mock(PushNotificationService::class);
    $mock2->shouldReceive('send')->never();

    $job2 = new SendMemberWelcomePush($member2->id);
    $job2->handle($mock2);
});

test('LoyaltyPointsUpdatedNotification respects preferences', function () {
    // Member 1: enabled
    $member1 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => true, 'loyalty' => true]],
    ]);
    $member1->deviceTokens()->create(['token' => 'token1', 'is_active' => true, 'device_type' => 'ios']);

    $mock = mock(PushNotificationService::class);
    $mock->shouldReceive('send')->once();
    $this->app->instance(PushNotificationService::class, $mock);

    $notification = new LoyaltyPointsUpdatedNotification($member1, 10, 'gift', 'Welcome bonus');
    $notification->toArray($member1);

    // Member 2: disabled
    $member2 = Member::factory()->create([
        'preferences' => ['notifications' => ['push_enabled' => true, 'loyalty' => false]],
    ]);
    $member2->deviceTokens()->create(['token' => 'token2', 'is_active' => true, 'device_type' => 'ios']);

    $mock2 = mock(PushNotificationService::class);
    $mock2->shouldReceive('send')->never();
    $this->app->instance(PushNotificationService::class, $mock2);

    $notification2 = new LoyaltyPointsUpdatedNotification($member2, 10, 'gift', 'Welcome bonus');
    $notification2->toArray($member2);
});
