<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Member;
use App\Services\Members\PushNotificationService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCourseCancelledPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $courseSessionId,
        public string $date
    ) {
        $this->onQueue('notifications');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(PushNotificationService $pushNotificationService): void
    {
        $session = CourseSession::find($this->courseSessionId);

        if (! $session) {
            return;
        }

        // Get members affected
        $memberIds = Booking::where('course_session_id', $this->courseSessionId)
            ->whereDate('date', $this->date)
            ->pluck('member_id')
            ->toArray();

        if (empty($memberIds)) {
            return;
        }

        $members = Member::query()
            ->with(['deviceTokens' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->whereIn('id', $memberIds)
            ->get();

        $tokens = [];
        foreach ($members as $member) {
            $prefs = $member->preferences['notifications'] ?? [];
            if (! ($prefs['push_enabled'] ?? true) || (isset($prefs['courses']) && ! $prefs['courses'])) {
                continue;
            }

            $memberTokens = $member->deviceTokens
                ->pluck('token')
                ->filter(fn (?string $token): bool => is_string($token) && $token !== '')
                ->values()
                ->all();

            $tokens = array_merge($tokens, $memberTokens);
        }

        $tokens = array_unique($tokens);

        if (empty($tokens)) {
            return;
        }

        $formattedDate = Carbon::parse($this->date)->format('M j, Y');

        $pushNotificationService->send(
            $tokens,
            'Class Cancelled: '.$session->course->name,
            'Your class scheduled for '.$formattedDate.' at '.Carbon::parse($session->starts_at)->format('H:i').' has been cancelled.',
            [
                'type' => 'course_cancelled',
                'course_session_id' => (string) $session->id,
                'date' => $this->date,
            ],
        );
    }
}
