<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public int $pointsChanged,
        public string $type, // 'gift' or 'refund'
        public string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->member->fallback_email,
            subject: $this->type === 'gift' ? __('Points Gifted from Bourgo Arena') : __('Loyalty Balance Adjusted'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.loyalty.update',
        );
    }
}
