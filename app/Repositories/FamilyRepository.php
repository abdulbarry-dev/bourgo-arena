<?php

namespace App\Repositories;

use App\Models\Member;
use Illuminate\Support\Collection;

class FamilyRepository
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

    /**
     * Create a child record.
     */
    public function createChild(Member $parent, array $data): Member
    {
        return $parent->children()->create($data);
    }

    /**
     * Update a child record.
     */
    public function updateChild(Member $child, array $data): Member
    {
        $child->update($data);

        return $child->fresh();
    }

    /**
     * Disable family account.
     */
    public function disableFamilyAccount(Member $member): void
    {
        $member->update(['is_family_account' => false]);
    }

    /**
     * Archive all children of a parent.
     */
    public function archiveChildren(Member $parent): void
    {
        $parent->children()->update(['is_archived' => true]);
    }

    /**
     * Enable family account.
     */
    public function enableFamilyAccount(Member $member): Member
    {
        $member->update(['is_family_account' => true]);

        return $member->fresh();
    }

    /**
     * Delete a child record.
     */
    public function deleteChild(Member $child): bool
    {
        return $child->delete();
    }
}
