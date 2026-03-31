<?php

namespace App\Actions\Terminals;

use App\Models\HikvisionTerminal;
use Illuminate\Support\Str;

class ProvisionTerminalAction
{
    public function execute(array $data): HikvisionTerminal
    {
        $data['api_token'] = $this->generateUniqueToken();
        $data['status'] = 'offline';

        return HikvisionTerminal::query()->create($data);
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (HikvisionTerminal::query()->where('api_token', $token)->exists());

        return $token;
    }
}
