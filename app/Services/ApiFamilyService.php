<?php

namespace App\Services;

use App\DTOs\FamilyChildDTO;
use App\Models\Member;
use App\Repositories\FamilyRepository;

class ApiFamilyService
{
    public function __construct(
        protected FamilyRepository $familyRepository
    ) {}

    /**
     * Create a child record under the provided parent member.
     */
    public function createChild(Member $parent, FamilyChildDTO $dto): Member
    {
        return $this->familyRepository->createChild($parent, $this->mapChildPayload($dto));
    }

    /**
     * Update a child only when it belongs to the authenticated parent.
     */
    public function updateChild(Member $parent, Member $child, FamilyChildDTO $dto): ?Member
    {
        if (! $this->isOwnedByParent($parent, $child)) {
            return null;
        }

        return $this->familyRepository->updateChild($child, $this->mapChildPayload($dto));
    }

    /**
     * Disable family account and archive all children.
     */
    public function disableFamilyAccount(Member $member): void
    {
        $this->familyRepository->disableFamilyAccount($member);
        $this->familyRepository->archiveChildren($member);
    }

    /**
     * Enable family account feature for the member.
     */
    public function enableFamilyAccount(Member $member): Member
    {
        return $this->familyRepository->enableFamilyAccount($member);
    }

    /**
     * Delete a child only when it belongs to the authenticated parent.
     */
    public function deleteChild(Member $parent, Member $child): bool
    {
        if (! $this->isOwnedByParent($parent, $child)) {
            return false;
        }

        return $this->familyRepository->deleteChild($child);
    }

    private function isOwnedByParent(Member $parent, Member $child): bool
    {
        return $child->parent_id === $parent->id;
    }

    /**
     * Map DTO to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    private function mapChildPayload(FamilyChildDTO $dto): array
    {
        return [
            'name' => trim(($dto->firstName ?? '').' '.($dto->lastName ?? '')),
            'date_of_birth' => $dto->birthDate,
            'gender' => $dto->gender,
            'status' => 'active',
            'password' => null,
        ];
    }
}
