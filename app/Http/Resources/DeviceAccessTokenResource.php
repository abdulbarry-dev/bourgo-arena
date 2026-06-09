<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceAccessTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'token' => $this->token,
            'platform' => $this->platform,
            'app_version' => $this->app_version,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
