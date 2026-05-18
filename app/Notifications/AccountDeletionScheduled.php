<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\AccountDeletionScheduledMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionScheduled extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
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
        $channels = [];

        if ($notifiable->email_verified_at) {
            $channels[] = 'mail';
        }

        if ($notifiable->phone_verified_at) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable|MailMessage
    {
        return new AccountDeletionScheduledMail($notifiable);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return __('Your account is scheduled for deletion in 48h. To cancel, simply log back into the app and verify your identity with an OTP.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_deletion_scheduled',
            'scheduled_at' => $notifiable->scheduled_for_deletion_at,
        ];
    }
}
