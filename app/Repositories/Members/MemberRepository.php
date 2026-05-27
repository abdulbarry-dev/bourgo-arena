<?php

namespace App\Repositories\Members;

use App\Models\Member;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberRepository
{
    public function loadProfileRelations(Member $member): Member
    {
        return $member->load(['activeSubscription.plan', 'children']);
    }

    public function updateProfile(Member $member, array $data): Member
    {
        $member->update($data);

        return $member->fresh();
    }

    public function scheduleDeletion(Member $member, CarbonInterface $scheduledAt): bool
    {
        return $member->update([
            'scheduled_for_deletion_at' => $scheduledAt,
        ]);
    }

    public function getReservationsPaginated(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->reservations()
            ->with(['activity', 'slot'])
            ->latest()
            ->paginate($perPage);
    }
}
