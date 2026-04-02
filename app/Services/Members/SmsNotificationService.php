<?php

namespace App\Services\Members;

use App\Models\Member;
use Illuminate\Support\Facades\Http;

class SmsNotificationService
{
    public function sendWelcomeMessage(Member $member): void
    {
        $accountSid = (string) config('services.twilio.account_sid');
        $authToken = (string) config('services.twilio.auth_token');
        $fromNumber = (string) config('services.twilio.from_number');

        if ($accountSid === '' || $authToken === '' || $fromNumber === '') {
            return;
        }

        $endpoint = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $accountSid);

        Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post($endpoint, [
                'To' => $member->phone,
                'From' => $fromNumber,
                'Body' => sprintf(
                    'Welcome to Bourgo Arena, %s. Your account was created successfully. Please check your email for onboarding instructions and temporary password details.',
                    $member->name,
                ),
            ])
            ->throw();
    }
}
