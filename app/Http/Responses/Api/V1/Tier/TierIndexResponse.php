<?php

namespace App\Http\Responses\Api\V1\Tier;

use App\Http\Resources\Api\V1\MembershipTiersResource;
use App\Http\Responses\Api\V1\ApiBaseResponse;
use Illuminate\Support\Collection;

class TierIndexResponse extends ApiBaseResponse
{
    /**
     * @param  array{tiers: Collection, family_tiers: Collection}  $tiers
     */
    public function __construct(array $tiers)
    {
        parent::__construct(new MembershipTiersResource($tiers));
    }
}
