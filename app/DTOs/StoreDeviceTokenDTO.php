<?php

namespace App\DTOs;

class StoreDeviceTokenDTO
{
    public function __construct(
        public readonly string $token,
        public readonly ?string $deviceType = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            token: $data['token'],
            deviceType: $data['device_type'] ?? null,
        );
    }
}
