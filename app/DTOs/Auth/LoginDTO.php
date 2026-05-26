<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class LoginDTO
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly string $password = ''
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            email: $request->input('email'),
            phone: $request->input('phone'),
            password: $request->input('password', '')
        );
    }
}
