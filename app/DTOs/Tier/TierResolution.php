<?php

namespace App\DTOs\Tier;

class TierResolution
{
    public function __construct(
        public TierData $currentTier,
        public int $currentSubscriptionCount,
        public ?TierData $nextTier = null,
        public int $progressPercentage = 100
    ) {}
}
