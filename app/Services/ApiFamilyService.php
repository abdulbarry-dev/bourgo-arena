<?php

namespace App\Services;

use App\Models\Member;

class ApiFamilyService
{
    /**
     * Create a child record under the provided parent member.
     *
     * @param  array<string, mixed>  $validatedData
     */
    public function createChild(Member $parent, array $validatedData): Member
    {
        return $parent->children()->create($this->mapChildPayload($validatedData));
    }

    /**
     * Update a child only when it belongs to the authenticated parent.
     *
     * @param  array<string, mixed>  $validatedData
     */
    public function updateChild(Member $parent, Member $child, array $validatedData): ?Member
    {
        if (! $this->isOwnedByParent($parent, $child)) {
            return null;
        }

        $child->update($this->mapChildPayload($validatedData));

        return $child->fresh();
    }

    /**
     * Disable family account and archive all children.
     */
    public function disableFamilyAccount(Member $member): void
    {
        $member->update(['is_family_account' => false]);
        $member->children()->update(['is_archived' => true]);
    }

    /**
     * Delete a child only when it belongs to the authenticated parent.
     */
    public function deleteChild(Member $parent, Member $child): bool
    {
        if (! $this->isOwnedByParent($parent, $child)) {
            return false;
        }

        $child->delete();

        return true;
    }

    private function isOwnedByParent(Member $parent, Member $child): bool
    {
        return $child->parent_id === $parent->id;
    }

    /**
     * @param  array<string, mixed>  $validatedData
     * @return array<string, mixed>
     */
    private function mapChildPayload(array $validatedData): array
    {
        return [
            'name' => trim(($validatedData['first_name'] ?? '').' '.($validatedData['last_name'] ?? '')),
            'date_of_birth' => $validatedData['birth_date'],
            'gender' => $validatedData['gender'],
            'status' => 'active',
            'password' => null,
        ];
    }
}
