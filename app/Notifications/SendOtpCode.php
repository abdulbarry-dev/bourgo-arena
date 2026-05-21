<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\SendOtpCodeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $code,
        public ?string $preferredChannel = null
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // If an explicit preferred channel was provided, respect it.
        if ($this->preferredChannel === 'mail') {
            return ['mail'];
        }

        if ($this->preferredChannel === 'sms') {
            return [SmsChannel::class];
        }

        // Try to detect verified contact methods on the notifiable and send
        // the OTP to all verified channels. If neither verification flag is
        // present (anonymous/not route-based delivery), fall back to existing
        // heuristics.
        $channels = [];

        $email = $notifiable->routeNotificationFor('mail') ?: ($notifiable->email ?? null);
        $phone = $notifiable->routeNotificationFor('sms') ?: ($notifiable->phone ?? null);

        $hasEmailVerified = $notifiable->email_verified_at ?? null;
        $hasPhoneVerified = $notifiable->phone_verified_at ?? null;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && $hasEmailVerified) {
            $channels[] = 'mail';
        }

        if ($phone && $hasPhoneVerified) {
            $channels[] = SmsChannel::class;
        }

        // If we found verified channels, return them (may be both).
        if (! empty($channels)) {
            return $channels;
        }

        // Fallback: if the notifiable has an email, prefer mail; else sms.
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['mail'];
        }

        if ($phone) {
            return [SmsChannel::class];
        }

        // Last resort - prefer mail for anonymous deliveries.
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable|MailMessage
    {
        return new SendOtpCodeMail(
            code: $this->code,
            userEmail: $notifiable->email ?? $notifiable->routeNotificationFor('mail'),
            userName: $notifiable->name ?? null,
        );
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return __('Your verification code is: :code. Valid for :minutes minutes.', [
            'code' => $this->code,
            'minutes' => config('otp.expiry', 10),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
