<?php

use App\Models\Member;
use App\Models\MemberDigitalNfcDevice;
use App\Models\NfcCard;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'onboarding_completed_at' => now(),
        'email_verified_at' => now(),
        'pin' => '1234',
    ]);
    Sanctum::actingAs($this->member);
});

describe('Physical NFC Status', function () {
    it('returns active card status when assigned', function () {
        NfcCard::factory()->create([
            'member_id' => $this->member->id,
            'status' => 'active',
            'uid' => 'A1B2C3D4',
        ]);

        $response = $this->getJson(route('api.v1.member.nfc.physical-status'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_card' => true,
                    'card_uid' => 'A1B2C3D4',
                    'card_status' => 'active',
                    'is_ready' => true,
                    'fallback_methods' => ['pin', 'physical_card'],
                ],
            ]);
    });

    it('returns no card status when none assigned', function () {
        $response = $this->getJson(route('api.v1.member.nfc.physical-status'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_card' => false,
                    'card_uid' => null,
                    'card_status' => null,
                    'is_ready' => false,
                ],
            ]);
    });

    it('returns inactive ready state for suspended cards', function () {
        NfcCard::factory()->suspended()->create([
            'member_id' => $this->member->id,
        ]);

        $response = $this->getJson(route('api.v1.member.nfc.physical-status'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_card' => true,
                    'card_status' => 'suspended',
                    'is_ready' => false,
                ],
            ]);
    });
});

describe('Digital NFC Status', function () {
    it('returns supported status for compatible devices', function () {
        $response = $this->getJson(route('api.v1.member.nfc.digital-status', [
            'device_model' => 'Samsung Galaxy S24',
            'os_version' => 'Android 14',
            'nfc_enabled' => true,
            'supports_hce' => true,
        ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'supported' => true,
                    'eligible' => true,
                    'is_ready' => false,
                    'setup_status' => 'not_started',
                ],
            ]);
    });

    it('returns unsupported status for blocked manufacturers', function () {
        $response = $this->getJson(route('api.v1.member.nfc.digital-status', [
            'device_model' => 'Huawei P30',
            'os_version' => 'Android 14',
            'nfc_enabled' => true,
            'supports_hce' => true,
        ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'supported' => false,
                ],
            ])
            ->assertJsonFragment(['manufacturer_blocked']);
    });

    it('returns ready status when setup is completed', function () {
        MemberDigitalNfcDevice::create([
            'member_id' => $this->member->id,
            'device_identifier' => 'test-device',
            'device_model' => 'Samsung Galaxy S24',
            'os_version' => 'Android 14',
            'supports_hce' => true,
            'nfc_enabled' => true,
            'is_supported' => true,
            'setup_status' => 'completed',
            'is_active' => true,
        ]);

        $response = $this->getJson(route('api.v1.member.nfc.digital-status', [
            'device_model' => 'Samsung Galaxy S24',
            'os_version' => 'Android 14',
            'nfc_enabled' => true,
            'supports_hce' => true,
        ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'supported' => true,
                    'configured' => true,
                    'is_ready' => true,
                    'setup_status' => 'completed',
                ],
            ]);
    });
});

describe('Digital NFC Setup', function () {
    it('initializes setup for supported devices', function () {
        $payload = [
            'device_identifier' => 'new-device-id',
            'device_model' => 'Samsung Galaxy S24',
            'os_version' => 'Android 14',
            'nfc_enabled' => true,
            'supports_hce' => true,
        ];

        $response = $this->postJson(route('api.v1.member.nfc.digital-setup'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'setup_status' => 'completed',
                    'supported' => true,
                    'eligible' => true,
                ],
            ]);

        $this->assertDatabaseHas('member_digital_nfc_devices', [
            'member_id' => $this->member->id,
            'device_identifier' => 'new-device-id',
            'setup_status' => 'completed',
        ]);
    });

    it('deactivates previous devices on new setup', function () {
        $oldDevice = MemberDigitalNfcDevice::create([
            'member_id' => $this->member->id,
            'device_identifier' => 'old-device-id',
            'is_active' => true,
        ]);

        $payload = [
            'device_identifier' => 'new-device-id',
            'device_model' => 'Samsung Galaxy S24',
            'os_version' => 'Android 14',
            'nfc_enabled' => true,
            'supports_hce' => true,
        ];

        $this->postJson(route('api.v1.member.nfc.digital-setup'), $payload);

        expect($oldDevice->fresh()->is_active)->toBeFalse();
    });
});
