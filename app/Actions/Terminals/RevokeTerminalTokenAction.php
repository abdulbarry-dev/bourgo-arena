<?php

namespace App\Actions\Terminals;

use App\Models\HikvisionTerminal;
use Illuminate\Support\Str;

class RevokeTerminalTokenAction
{
    public function execute(HikvisionTerminal $terminal): HikvisionTerminal
    {
        $terminal->update([
            'api_token' => $this->generateUniqueToken(),
            'status' => 'offline',
            'last_seen_at' => null,
        ]);

        return $terminal;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (HikvisionTerminal::query()->where('api_token', $token)->exists());

        return $token;
    }
}
