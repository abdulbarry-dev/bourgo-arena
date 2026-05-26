<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class FamilyChildDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $birthDate,
        public string $gender,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            firstName: $request->validated('first_name'),
            lastName: $request->validated('last_name'),
            birthDate: $request->validated('birth_date'),
            gender: $request->validated('gender'),
        );
    }
}
