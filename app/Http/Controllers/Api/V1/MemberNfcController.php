<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DigitalNfcSetupRequest;
use App\Http\Requests\Api\V1\DigitalNfcStatusRequest;
use App\Http\Resources\Api\V1\DigitalNfcStatusResource;
use App\Http\Resources\Api\V1\PhysicalNfcStatusResource;
use App\Services\Nfc\DigitalNfcCompatibilityService;
use App\Services\Nfc\DigitalNfcSetupService;
use App\Services\Nfc\PhysicalNfcStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberNfcController extends Controller
{
    public function __construct(
        protected PhysicalNfcStatusService $physicalNfcStatusService,
        protected DigitalNfcCompatibilityService $digitalNfcCompatibilityService,
        protected DigitalNfcSetupService $digitalNfcSetupService
    ) {}

    /**
     * Get the physical NFC status for the authenticated member.
     */
    public function physicalStatus(Request $request): JsonResponse
    {
        $status = $this->physicalNfcStatusService->getStatus($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Physical NFC status retrieved.',
            'data' => new PhysicalNfcStatusResource($status),
        ]);
    }

    /**
     * Get the digital NFC status for the authenticated member.
     */
    public function digitalStatus(DigitalNfcStatusRequest $request): JsonResponse
    {
        $member = $request->user();
        $deviceData = $request->validated();

        $compatibility = $this->digitalNfcCompatibilityService->checkCompatibility($deviceData);

        $existingDevice = $member->digitalNfcDevices()
            ->where('is_active', true)
            ->first();

        $isEligible = $compatibility['supported'] && $member->isActive();
        $isReady = $isEligible && $existingDevice?->isCompleted();

        $status = [
            'supported' => $compatibility['supported'],
            'configured' => $existingDevice !== null,
            'eligible' => $isEligible,
            'is_ready' => $isReady,
            'setup_status' => $existingDevice?->setup_status ?? ($compatibility['supported'] ? 'not_started' : 'unsupported'),
            'reasons' => $compatibility['reasons'],
            'fallback_methods' => $this->getFallbackMethods($member),
        ];

        return response()->json([
            'success' => true,
            'message' => $compatibility['supported'] ? 'Digital NFC supported.' : 'Device does not support digital NFC.',
            'data' => new DigitalNfcStatusResource($status),
        ]);
    }

    /**
     * Set up or update a digital NFC device for the authenticated member.
     */
    public function setupDigital(DigitalNfcSetupRequest $request): JsonResponse
    {
        $member = $request->user();
        $device = $this->digitalNfcSetupService->setup($member, $request->validated());

        return response()->json([
            'success' => true,
            'message' => $device->is_supported ? 'Digital NFC setup initialized.' : 'Device does not support digital NFC.',
            'data' => [
                'setup_status' => $device->setup_status,
                'supported' => $device->is_supported,
                'eligible' => $device->is_supported && $member->isActive(),
            ],
        ]);
    }

    protected function getFallbackMethods($member): array
    {
        $methods = ['pin'];

        if ($member->nfcCard()->where('status', 'active')->exists()) {
            $methods[] = 'physical_card';
        }

        return $methods;
    }
}
