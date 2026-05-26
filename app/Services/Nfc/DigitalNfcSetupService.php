<?php

namespace App\Services\Nfc;

use App\DTOs\DigitalNfcSetupDTO;
use App\Models\Member;
use App\Models\MemberDigitalNfcDevice;

class DigitalNfcSetupService
{
    public function __construct(
        protected DigitalNfcCompatibilityService $compatibilityService
    ) {}

    /**
     * Set up or update a digital NFC device for a member.
     */
    public function setup(Member $member, DigitalNfcSetupDTO $dto): MemberDigitalNfcDevice
    {
        $compatibility = $this->compatibilityService->checkCompatibility($dto);

        // Deactivate other devices for this member (one active device rule)
        $member->digitalNfcDevices()->update(['is_active' => false]);

        return $member->digitalNfcDevices()->updateOrCreate(
            ['device_identifier' => $dto->deviceIdentifier],
            [
                'device_model' => $dto->deviceModel,
                'os_version' => $dto->osVersion,
                'supports_hce' => $dto->supportsHce,
                'nfc_enabled' => $dto->nfcEnabled,
                'is_supported' => $compatibility['supported'],
                'setup_status' => $compatibility['supported'] ? 'completed' : 'unsupported',
                'is_active' => true,
                'last_verified_at' => now(),
            ]
        );
    }
}
