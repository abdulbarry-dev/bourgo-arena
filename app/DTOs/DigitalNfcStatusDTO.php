<?php

namespace App\DTOs;

class DigitalNfcStatusDTO
{
    public function __construct(
        public readonly string $deviceModel,
        public readonly string $osVersion,
        public readonly bool $nfcEnabled,
        public readonly bool $supportsHce,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            deviceModel: $data['device_model'],
            osVersion: $data['os_version'],
            nfcEnabled: (bool) $data['nfc_enabled'],
            supportsHce: (bool) $data['supports_hce'],
        );
    }

    public function toArray(): array
    {
        return [
            'device_model' => $this->deviceModel,
            'os_version' => $this->osVersion,
            'nfc_enabled' => $this->nfcEnabled,
            'supports_hce' => $this->supportsHce,
        ];
    }
}
