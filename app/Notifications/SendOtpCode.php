<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $code)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $identifier = $notifiable->routeNotificationFor('mail') ?: $notifiable->email;

        if ($identifier && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return ['mail'];
        }

        return [SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your OTP Verification Code'))
            ->greeting(__('Hello!'))
            ->line(__('Your verification code is:'))
            ->line($this->code)
            ->line(__('This code will expire in :minutes minutes.', ['minutes' => config('otp.expiry', 10)]))
            ->line(__('If you did not request this code, no further action is required.'));
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
