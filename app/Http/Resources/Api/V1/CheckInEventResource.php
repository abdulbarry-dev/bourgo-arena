<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     checked_in_at: string,
     *     location: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'location' => $this->terminal?->location,
        ];
    }
}
