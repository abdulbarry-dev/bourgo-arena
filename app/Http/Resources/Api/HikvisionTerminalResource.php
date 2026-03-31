<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class HikvisionTerminalResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'ip_address' => $this->ip_address,
            'location' => $this->location,
            'terminal_type' => $this->terminal_type,
            'status' => $this->status,
            'last_seen_at' => $this->last_seen_at,
            'api_token' => $this->whenNotNull($this->api_token), // Return conditionally based on use case
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
