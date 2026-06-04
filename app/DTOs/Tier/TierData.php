<?php

namespace App\DTOs\Tier;

class TierData
{
    public function __construct(
        public string $label,
        public float $multiplier,
        public int $requiredSubscriptions,
        public ?string $requirements = null,
        public ?string $benefits = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'],
            multiplier: (float) $data['multiplier'],
            requiredSubscriptions: (int) $data['required_subscriptions'],
            requirements: $data['requirements'] ?? null,
            benefits: $data['benefits'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'multiplier' => $this->multiplier,
            'required_subscriptions' => $this->requiredSubscriptions,
            'requirements' => __($this->requirements),
            'benefits' => __($this->benefits),
        ];
    }
}
