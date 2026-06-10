<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        // Handle both Model notifiables and Anonymous notifiables
        $to = $notifiable->routeNotificationFor(static::class)
            ?? $notifiable->routeNotificationFor('sms')
            ?? ($notifiable->phone ?? null);

        if (! $to) {
            Log::warning('SmsChannel: No phone number found for notifiable.', [
                'notifiable' => get_class($notifiable),
            ]);

            return;
        }

        // Clean number and ensure +216 for 8-digit Tunisian numbers
        $to = preg_replace('/[^0-9+]/', '', (string) $to);
        if (strlen($to) === 8 && ctype_digit($to)) {
            $to = '+216'.$to;
        }

        $sid = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');
        $from = config('services.twilio.from_number');

        if (! $sid || ! $token || ! $from) {
            Log::error('Twilio credentials not set in config/services.php');

            return;
        }

        try {
            $twilio = $this->createClient($sid, $token);

            $twilio->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);

            Log::info("SMS OTP sent to {$to} via Twilio.");
        } catch (\Exception $e) {
            Log::error("Failed to send SMS OTP to {$to} via Twilio: ".$e->getMessage());
        }
    }

    protected function createClient(string $sid, string $token): Client
    {
        return app(Client::class, [
            'username' => $sid,
            'password' => $token,
        ]);
    }
}
