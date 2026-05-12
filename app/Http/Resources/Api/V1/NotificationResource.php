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
            /** @description The unique identifier of the notification. @example 1 */
            'id' => $this->id,
            /** @description The notification title. @example "Booking Confirmed" */
            'title' => $this->title,
            /** @description The detailed message content of the notification. @example "Your reservation for Padel has been confirmed." */
            'message' => $this->message,
            /** @description The category or type of notification. @example "reservation_update" */
            'type' => $this->type,
            /** @description Whether the notification has been read by the member. @example false */
            'is_read' => (bool) $this->is_read,
            /** @description When the notification was sent. @format date-time @example "2024-05-12T14:30:00Z" */
            'timestamp' => $this->created_at->toIso8601String(),
        ];
    }
}
