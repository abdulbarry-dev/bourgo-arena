<?php

namespace App\DTOs;

readonly class GeoLocationDTO
{
    public function __construct(
        public string $countryCode,
        public string $countryName,
        public ?string $city,
        public ?string $isp,
        public string $ip,
    ) {}

    public function isTunisian(): bool
    {
        return $this->countryCode === 'TN';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'city' => $this->city,
            'isp' => $this->isp,
            'ip' => $this->ip,
        ];
    }
}
