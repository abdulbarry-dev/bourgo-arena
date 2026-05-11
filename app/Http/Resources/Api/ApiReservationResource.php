<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySlotResource;
use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class ApiReservationResource extends BaseJsonResource
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
            'member_id' => $this->member_id,
            'activity_id' => $this->activity_id,
            'activity_slot_id' => $this->activity_slot_id,
            'activity_title' => $this->activity?->title,
            'date' => $this->date->toDateString(),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'price' => (float) $this->price,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'qr_code' => $this->qr_code,
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'slot' => new ActivitySlotResource($this->whenLoaded('slot')),
        ];
    }
}
