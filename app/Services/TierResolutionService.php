<?php

namespace App\Services;

use App\Models\Member;

class TierResolutionService
{
    public function __construct(
        protected ApiSubscriptionRepository $subscriptionRepository,
        protected ApiFamilyRepository $familyRepository
    ) {}

    /**
     * Resolve the tier for a given member.
     *
     * @return array{label: string, multiplier: float}
     */
    public function resolveTier(Member $member): array
    {
        if ($member->parent_id !== null) {
            $parent = $member->parent()->first();

            if ($parent !== null) {
                $member = $parent;
            }
        }

        if ($member->is_family_account) {
            return $this->resolveFamilyTier($member);
        }

        return $this->resolveIndividualTier($member);
    }

    /**
     * Resolve tier for individual accounts based on active subscription count.
     */
    protected function resolveIndividualTier(Member $member): array
    {
        $count = $this->subscriptionRepository->getValidSubscriptionCount($member);

        return match (true) {
            $count >= 4 => ['label' => 'Max', 'multiplier' => 2.0],
            $count === 3 => ['label' => 'Ultra', 'multiplier' => 1.5],
            $count === 2 => ['label' => 'Plus', 'multiplier' => 1.2],
            default => ['label' => 'Standard', 'multiplier' => 1.0],
        };
    }

    /**
     * Resolve tier for family accounts based on family member count.
     */
    protected function resolveFamilyTier(Member $member): array
    {
        $memberIds = $this->familyRepository->getFamilyMemberIds($member)->all();
        $count = $this->subscriptionRepository->getValidSubscriptionCountForMemberIds($memberIds);

        return match (true) {
            $count >= 4 => ['label' => 'Family Max', 'multiplier' => 2.0],
            $count === 3 => ['label' => 'Family Ultra', 'multiplier' => 1.5],
            $count === 2 => ['label' => 'Family Plus', 'multiplier' => 1.2],
            default => ['label' => 'Family', 'multiplier' => 1.0],
        };
    }
}
