<?php

namespace App\Services\Nfc;

use App\DTOs\DigitalNfcSetupDTO;
use App\DTOs\DigitalNfcStatusDTO;
use Illuminate\Support\Str;

class DigitalNfcCompatibilityService
{
    /**
     * Check if a device is compatible with digital NFC access.
     */
    public function checkCompatibility(DigitalNfcStatusDTO|DigitalNfcSetupDTO $dto): array
    {
        $model = $dto->deviceModel;
        $osVersion = $dto->osVersion;
        $supportsHce = $dto->supportsHce;
        $nfcEnabled = $dto->nfcEnabled;

        $isSupportedModel = $this->isModelSupported($model);
        $isSupportedOs = $this->isOsVersionSupported($osVersion);
        $isNotBlocked = $this->isNotBlocked($model);

        $isSupported = $isSupportedModel && $isSupportedOs && $isNotBlocked && $supportsHce;

        return [
            'supported' => $isSupported,
            'reasons' => $this->getIncompatibilityReasons($isSupportedModel, $isSupportedOs, $isNotBlocked, $supportsHce, $nfcEnabled),
        ];
    }

    protected function isModelSupported(string $model): bool
    {
        $supportedModels = config('digital_nfc.supported_models', []);

        if (empty($supportedModels)) {
            return true;
        }

        foreach ($supportedModels as $supportedModel) {
            if (Str::contains(strtolower($model), strtolower($supportedModel))) {
                return true;
            }
        }

        return false;
    }

    protected function isOsVersionSupported(string $osVersion): bool
    {
        $minVersion = config('digital_nfc.minimum_android_version', 12);

        // Extract numeric version if it's like "Android 14"
        preg_match('/\d+/', $osVersion, $matches);
        $versionNumber = $matches[0] ?? 0;

        return (int) $versionNumber >= $minVersion;
    }

    protected function isNotBlocked(string $model): bool
    {
        $blockedManufacturers = config('digital_nfc.blocked_manufacturers', []);

        foreach ($blockedManufacturers as $manufacturer) {
            if (Str::contains(strtolower($model), strtolower($manufacturer))) {
                return false;
            }
        }

        return true;
    }

    protected function getIncompatibilityReasons(bool $model, bool $os, bool $notBlocked, bool $hce, bool $nfc): array
    {
        $reasons = [];

        if (! $model) {
            $reasons[] = 'device_model_not_officially_supported';
        }

        if (! $os) {
            $reasons[] = 'os_version_too_low';
        }

        if (! $notBlocked) {
            $reasons[] = 'manufacturer_blocked';
        }

        if (! $hce) {
            $reasons[] = 'hce_not_supported_by_hardware';
        }

        if (! $nfc) {
            $reasons[] = 'nfc_disabled_on_device';
        }

        return $reasons;
    }
}
