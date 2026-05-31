<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class CompleteRegistrationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $date_of_birth,
        public readonly ?string $gender,
        public readonly bool $is_parent_account
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            date_of_birth: $request->input('date_of_birth'),
            gender: $request->input('gender'),
            is_parent_account: (bool) $request->input('is_parent_account', false)
        );
    }
}
