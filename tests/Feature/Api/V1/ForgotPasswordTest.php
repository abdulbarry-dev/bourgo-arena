<?php

namespace Tests\Feature\Api\V1;

use App\Channels\SmsChannel;
use App\Models\Member;
use App\Notifications\SendOtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_forgot_password_sends_otp_email()
    {
        Notification::fake();

        $member = Member::factory()->create([
            'email' => 'test@example.com',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson(route('api.v1.auth.forgot-password'), [
            'identifier' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        Notification::assertSentTo(
            $member,
            SendOtpCode::class,
            fn ($notification, $channels) => in_array('mail', $channels)
        );
    }

    public function test_forgot_password_sends_otp_via_sms_for_phone_identifier()
    {
        Notification::fake();

        $member = Member::factory()->create([
            'phone' => '12345678',
            'email' => 'test@example.com',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson(route('api.v1.auth.forgot-password'), [
            'identifier' => '12345678',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(
            $member,
            SendOtpCode::class,
            fn ($notification, $channels) => in_array(SmsChannel::class, $channels)
        );
    }
}
