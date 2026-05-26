<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $password,
        public readonly ?string $date_of_birth,
        public readonly ?string $gender,
        public readonly bool $is_family_account
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            password: $request->input('password'),
            date_of_birth: $request->input('date_of_birth'),
            gender: $request->input('gender'),
            is_family_account: (bool) $request->input('is_family_account', false)
        );
    }
}
