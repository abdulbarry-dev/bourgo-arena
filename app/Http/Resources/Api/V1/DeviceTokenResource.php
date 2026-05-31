<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class DeviceTokenResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'device_type' => $this->device_type,
            'provider' => $this->provider,
            'is_active' => (bool) $this->is_active,
            'last_used_at' => $this->last_used_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
