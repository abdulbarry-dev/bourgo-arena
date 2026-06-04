<?php

namespace App\Services;

use App\DTOs\Tier\TierData;
use App\DTOs\Tier\TierResolution;
use App\Models\Member;
use Illuminate\Support\Collection;

class TierResolutionService
{
    public function __construct(
        protected ApiSubscriptionRepository $subscriptionRepository,
        protected ApiFamilyRepository $familyRepository
    ) {}

    /**
     * Resolve the tier for a given member.
     */
    public function resolveTier(Member $member): TierResolution
    {
        $resolvedMember = $this->resolveEffectiveMember($member);

        if ($resolvedMember->is_family_account) {
            return $this->resolveFamilyTier($resolvedMember);
        }

        return $this->resolveIndividualTier($resolvedMember);
    }

    /**
     * Get all available membership tiers grouped by type.
     *
     * @return array{tiers: Collection<int, TierData>, family_tiers: Collection<int, TierData>}
     */
    public function getAllTiers(): array
    {
        return [
            'tiers' => $this->getIndividualTiers(),
            'family_tiers' => $this->getFamilyTiers(),
        ];
    }

    /**
     * Resolve effective member (parent if child).
     */
    protected function resolveEffectiveMember(Member $member): Member
    {
        if ($member->parent_id !== null) {
            return $member->parent()->first() ?? $member;
        }

        return $member;
    }

    /**
     * Resolve tier for individual accounts.
     */
    protected function resolveIndividualTier(Member $member): TierResolution
    {
        $count = $this->subscriptionRepository->getValidSubscriptionCount($member);

        return $this->calculateTierDetails($count, $this->getIndividualTiers());
    }

    /**
     * Resolve tier for family accounts.
     */
    protected function resolveFamilyTier(Member $member): TierResolution
    {
        $memberIds = $this->familyRepository->getFamilyMemberIds($member)->all();
        $count = $this->subscriptionRepository->getValidSubscriptionCountForMemberIds($memberIds);

        return $this->calculateTierDetails($count, $this->getFamilyTiers());
    }

    /**
     * Calculate current tier, next tier, and progress using DTOs.
     *
     * @param  Collection<int, TierData>  $tiers
     */
    protected function calculateTierDetails(int $count, Collection $tiers): TierResolution
    {
        $currentTier = $tiers->first();
        $nextTier = null;

        foreach ($tiers as $index => $tier) {
            if ($count >= $tier->requiredSubscriptions) {
                $currentTier = $tier;
                $nextTier = $tiers->get($index + 1);
            } else {
                break;
            }
        }

        $progressPercentage = 100;
        if ($nextTier) {
            $prevRequired = $currentTier->requiredSubscriptions;
            $nextRequired = $nextTier->requiredSubscriptions;
            $progressPercentage = (int) (($count - $prevRequired) / ($nextRequired - $prevRequired) * 100);
        }

        return new TierResolution(
            currentTier: $currentTier,
            currentSubscriptionCount: $count,
            nextTier: $nextTier,
            progressPercentage: min(100, max(0, $progressPercentage))
        );
    }

    /**
     * Get individual tiers from configuration.
     *
     * @return Collection<int, TierData>
     */
    protected function getIndividualTiers(): Collection
    {
        return collect(config('tiers.individual'))->map(fn (array $tier) => TierData::fromArray($tier));
    }

    /**
     * Get family tiers from configuration.
     *
     * @return Collection<int, TierData>
     */
    protected function getFamilyTiers(): Collection
    {
        return collect(config('tiers.family'))->map(fn (array $tier) => TierData::fromArray($tier));
    }
}
