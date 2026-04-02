<?php

namespace App\Services\Members;

use App\Models\Member;
use App\Models\MemberNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberNotificationService
{
    public function paginatedForMember(Member $member, int $perPage = 20): LengthAwarePaginator
    {
        return MemberNotification::query()
            ->where('member_id', $member->id)
            ->latest('id')
            ->paginate($perPage);
    }
}
