<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
            ?? $notifiable->phone;

        if (! $to) {
            return;
        }

        // Logic for sending SMS via a provider (e.g. Twilio, Vonage) would go here.
        // For now, we log the SMS message to the application log.
        Log::info("SMS OTP sent to {$to}: {$message}");
    }
}
