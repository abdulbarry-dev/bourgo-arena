<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;

class ApiFamilyRepository
{
    /**
     * @return Collection<int, int>
     */
    public function getFamilyMemberIds(Member $member): Collection
    {
        $rootMemberId = $member->parent_id ?? $member->id;

        return Member::query()
            ->whereKey($rootMemberId)
            ->orWhere('parent_id', $rootMemberId)
            ->pluck('id');
    }
}
