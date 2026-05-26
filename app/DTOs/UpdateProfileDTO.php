<?php

namespace App\DTOs;

class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $avatarUrl = null,
        public readonly ?string $birthDate = null,
        public readonly ?string $gender = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            birthDate: $data['birth_date'] ?? null,
            gender: $data['gender'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatarUrl,
            'date_of_birth' => $this->birthDate,
            'gender' => $this->gender,
        ], fn ($value) => $value !== null);
    }
}
