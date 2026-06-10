<?php

use App\Jobs\SendMemberPasswordResetEmail;
use App\Models\Member;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('handle()', function () {
    it('delegates to OtpService::generate with member fallback_email', function () {
        $member = Member::factory()->create([
            'email' => 'reset-dispatch@example.com',
        ]);
        $mock = Mockery::mock(OtpService::class);
        $mock->shouldReceive('generate')
            ->once()
            ->with('reset-dispatch@example.com');

        $job = new SendMemberPasswordResetEmail($member->id);
        $job->handle($mock);
    });

    it('falls back to member phone when email is null', function () {
        $member = Member::factory()->create([
            'email' => null,
            'phone' => '22446688',
        ]);

        $mock = Mockery::mock(OtpService::class);
        $mock->shouldReceive('generate')
            ->once()
            ->with('22446688');

        $job = new SendMemberPasswordResetEmail($member->id);
        $job->handle($mock);
    });
    it('does not throw when member not found', function () {
        $mock = Mockery::mock(OtpService::class);
        $mock->shouldNotReceive('generate');

        $job = new SendMemberPasswordResetEmail(999);
        $job->handle($mock);

        expect(true)->toBeTrue();
    });

    it('does not call generate when identifier is empty', function () {
        $member = Member::factory()->create([
            'email' => null,
            'phone' => null,
        ]);
        $mock = Mockery::mock(OtpService::class);
        $mock->shouldNotReceive('generate');

        $job = new SendMemberPasswordResetEmail($member->id);
        $job->handle($mock);

        expect(true)->toBeTrue();
    });
});
