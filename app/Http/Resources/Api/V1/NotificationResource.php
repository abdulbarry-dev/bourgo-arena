<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     title: string,
     *     message: string,
     *     type: string,
     *     is_read: bool,
     *     timestamp: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'is_read' => (bool) $this->is_read,
            'timestamp' => $this->created_at->toIso8601String(),
        ];
    }
}
