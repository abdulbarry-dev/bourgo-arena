<?php

namespace App\Services\Members;

use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, string>  $data
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $serverKey = (string) config('services.fcm.server_key');

        if ($serverKey === '' || count($tokens) === 0) {
            return;
        }

        Http::withHeaders([
            'Authorization' => 'key='.$serverKey,
        ])
            ->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => array_values($tokens),
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ])
            ->throw();
    }
}
