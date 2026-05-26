<?php

namespace App\Repositories\Members;

use App\Models\Member;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberRepository
{
    public function loadProfileRelations(Member $member): Member
    {
        return $member->load(['activeSubscription.plan', 'children'])
            ->loadCount('checkInEvents');
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

    public function getAccessHistory(Member $member): Collection
    {
        return $member->checkInEvents()
            ->with('terminal')
            ->latest('checked_in_at')
            ->get();
    }

    public function getReservationsPaginated(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->reservations()
            ->with(['activity', 'slot'])
            ->latest()
            ->paginate($perPage);
    }
}
