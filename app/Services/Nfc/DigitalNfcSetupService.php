<?php

namespace App\Services\Nfc;

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
    public function setup(Member $member, array $data): MemberDigitalNfcDevice
    {
        $compatibility = $this->compatibilityService->checkCompatibility($data);

        // Deactivate other devices for this member (one active device rule)
        $member->digitalNfcDevices()->update(['is_active' => false]);

        return $member->digitalNfcDevices()->updateOrCreate(
            ['device_identifier' => $data['device_identifier']],
            [
                'device_model' => $data['device_model'],
                'os_version' => $data['os_version'],
                'supports_hce' => $data['supports_hce'],
                'nfc_enabled' => $data['nfc_enabled'],
                'is_supported' => $compatibility['supported'],
                'setup_status' => $compatibility['supported'] ? 'completed' : 'unsupported',
                'is_active' => true,
                'last_verified_at' => now(),
            ]
        );
    }
}
