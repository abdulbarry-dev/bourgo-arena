<?php

namespace App\Services\Terminals;

use App\Models\HikvisionTerminal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HikvisionService
{
    /**
     * Send a remote control command to a Hikvision terminal.
     */
    public function remoteControl(HikvisionTerminal $terminal, string $command = 'unlock'): bool
    {
        if (! $terminal->ip_address) {
            Log::error("Cannot send command to terminal {$terminal->name}: No IP address configured.");

            return false;
        }

        // Hikvision ISAPI Remote Control Endpoint
        // PUT /ISAPI/AccessControl/RemoteControl/door/1
        $url = "http://{$terminal->ip_address}/ISAPI/AccessControl/RemoteControl/door/1";

        try {
            $response = Http::withBasicAuth(
                config('services.hikvision.username', 'admin'),
                config('services.hikvision.password', '12345')
            )->put($url, [
                'RemoteControlDoor' => [
                    'command' => $command, // unlock, lock, alwaysOpen, alwaysClose
                ],
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("Hikvision Remote Control failed for {$terminal->name} ({$terminal->ip_address})", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Hikvision Remote Control exception for {$terminal->name}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Synchronize the whitelist to the terminal.
     */
    public function syncWhitelist(HikvisionTerminal $terminal, array $cardUids): bool
    {
        // Placeholder for ISAPI Whitelist Sync logic
        // This usually involves sending a list of card numbers to the terminal.
        Log::info('Synchronizing '.count($cardUids)." cards to terminal {$terminal->name}");

        return true;
    }
}
