<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;

class ApiFamilyRepository
{
    /**
     * Get the count of family members (children) for a parent member.
     */
    public function getFamilyMemberCount(Member $member): int
    {
        return $member->children()->count();
    }

    /**
     * Get the full family member IDs (parent + children).
     *
     * @return Collection<int, int>
     */
    public function getFamilyMemberIds(Member $member): Collection
    {
        $parent = $member->parent_id ? $member->parent()->first() : $member;

        if ($parent === null) {
            return collect([$member->id]);
        }

        $childIds = $parent->children()->pluck('id');

        return collect([$parent->id])->merge($childIds);
    }
}
