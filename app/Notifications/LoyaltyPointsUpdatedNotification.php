<?php

namespace App\Notifications;

use App\Mail\LoyaltyUpdateMail;
use App\Models\Member;
use App\Models\MemberNotification;
use App\Services\Members\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LoyaltyPointsUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Member $member,
        public int $pointsChanged,
        public string $type, // 'gift' or 'refund'
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): LoyaltyUpdateMail
    {
        return (new LoyaltyUpdateMail($this->member, $this->pointsChanged, $this->type, $this->reason))
            ->onQueue('emails');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->type === 'gift'
            ? __('Points Gifted from Bourgo Arena')
            : __('Loyalty Balance Adjusted');

        $message = $this->type === 'gift'
            ? __('You have received :points points from Bourgo Arena. Reason: :reason', [
                'points' => number_format($this->pointsChanged),
                'reason' => $this->reason,
            ])
            : __('Your loyalty balance was adjusted by :points points. Reason: :reason', [
                'points' => number_format($this->pointsChanged),
                'reason' => $this->reason,
            ]);

        // Explicitly create MemberNotification record for the dashboard and trigger Push
        $this->syncToMemberDashboardAndPush();

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'loyalty',
            'points_changed' => $this->pointsChanged,
            'reason' => $this->reason,
            'adjustment_type' => $this->type,
        ];
    }

    protected function syncToMemberDashboardAndPush(): void
    {
        $title = $this->type === 'gift'
            ? __('Points Gifted from Bourgo Arena')
            : __('Loyalty Balance Adjusted');

        $message = $this->type === 'gift'
            ? __('You have received :points points. Reason: :reason', [
                'points' => number_format($this->pointsChanged),
                'reason' => $this->reason,
            ])
            : __('Your loyalty balance was adjusted by :points points. Reason: :reason', [
                'points' => number_format($this->pointsChanged),
                'reason' => $this->reason,
            ]);

        // 1. Create Dashboard Notification Record
        MemberNotification::query()->create([
            'member_id' => $this->member->id,
            'type' => 'loyalty',
            'title' => $title,
            'message' => $message,
            'channel' => 'push',
            'status' => 'delivered',
            'is_read' => false,
            'metadata' => [
                'points_changed' => $this->pointsChanged,
                'reason' => $this->reason,
                'type' => $this->type,
            ],
            'delivered_at' => now(),
        ]);

        // 2. Trigger Mobile Push
        $tokens = $this->member->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (count($tokens) > 0) {
            app(PushNotificationService::class)->send(
                tokens: $tokens,
                title: $title,
                body: $message,
                data: [
                    'type' => 'loyalty_update',
                    'member_id' => (string) $this->member->id,
                ]
            );
        }
    }
}
