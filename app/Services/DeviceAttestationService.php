<?php

namespace App\Services;

class DeviceAttestationService
{
    public function verify(string $integrityToken, string $platform): bool
    {
        if (app()->isLocal() || app()->environment('testing')) {
            return $this->verifyDev($integrityToken);
        }

        return match ($platform) {
            'android' => $this->verifyPlayIntegrity($integrityToken),
            'ios' => $this->verifyAppAttest($integrityToken),
            default => false,
        };
    }

    protected function verifyPlayIntegrity(string $token): bool
    {
        // TODO: Implement real Play Integrity verification using google/apiclient.
        // Steps: decrypt the integrity token -> verify the signature -> check
        // the device integrity verdict (ctsProfileMatch, basicIntegrity, etc.).
        $projectNumber = config('services.play_integrity.project_number');
        $serviceAccountJson = config('services.play_integrity.service_account_json');

        if (! $projectNumber || ! $serviceAccountJson) {
            return false;
        }

        return true;
    }

    protected function verifyAppAttest(string $token): bool
    {
        // TODO: Implement real App Attest verification.
        // Steps: verify the attestation object against Apple's verification
        // endpoint -> validate the certificate chain -> check the key ID.
        $teamId = config('services.app_attest.team_id');
        $bundleId = config('services.app_attest.bundle_id');

        if (! $teamId || ! $bundleId) {
            return false;
        }

        return true;
    }

    protected function verifyDev(string $token): bool
    {
        return $token === config('app.dev_integrity_bypass_token');
    }

    public function verifyAppVersion(string $version, string $platform): bool
    {
        $minVersion = config('app.min_app_version.'.$platform);

        if (! $minVersion) {
            return false;
        }

        return version_compare($version, $minVersion, '>=');
    }
}
