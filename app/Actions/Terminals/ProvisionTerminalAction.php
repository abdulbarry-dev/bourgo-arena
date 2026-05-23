<?php

namespace App\Actions\Terminals;

use App\Models\HikvisionTerminal;
use Illuminate\Support\Str;

class ProvisionTerminalAction
{
    /**
     * @return array{terminal: HikvisionTerminal, plaintext_token: string}
     */
    public function execute(array $data): array
    {
        $plaintextToken = $this->generateUniqueToken();

        $data['api_token'] = hash('sha256', $plaintextToken);
        $data['status'] = 'offline';

        $terminal = HikvisionTerminal::query()->create($data);

        return [
            'terminal' => $terminal,
            'plaintext_token' => $plaintextToken,
        ];
    }

    private function generateUniqueToken(): string
    {
        return Str::random(64);
    }
}
