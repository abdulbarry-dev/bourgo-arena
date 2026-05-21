<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionScheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Model $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->user->email,
            subject: __('Account Deletion Scheduled'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account.deletion-scheduled',
        );
    }
}
