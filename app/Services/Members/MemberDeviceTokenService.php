<?php

namespace App\Services\Members;

use App\DTOs\StoreDeviceTokenDTO;
use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Repositories\Members\MemberDeviceTokenRepository;

class MemberDeviceTokenService
{
    public function __construct(private readonly MemberDeviceTokenRepository $repository) {}

    public function register(Member $member, StoreDeviceTokenDTO $dto): MemberDeviceToken
    {
        return $this->repository->upsertForMember($member, $dto->token, $dto->deviceType);
    }

    public function deactivate(Member $member, string $token): void
    {
        $this->repository->deactivateForMember($member, $token);
    }
}
