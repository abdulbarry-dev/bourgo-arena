<?php

namespace App\Http\Responses\Api\V1\Tier;

use App\DTOs\Tier\TierResolution;
use App\Http\Resources\Api\V1\MemberTierResource;
use App\Http\Responses\Api\V1\ApiBaseResponse;

class MemberTierResponse extends ApiBaseResponse
{
    public function __construct(TierResolution $tierResolution)
    {
        parent::__construct(new MemberTierResource($tierResolution));
    }
}
