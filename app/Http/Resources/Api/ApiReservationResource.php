<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySlotResource;
use App\Http\Resources\BaseJsonResource;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiReservationResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     member_id: int,
     *     activity_id: int,
     *     activity_slot_id: int,
     *     activity_title: string,
     *     date: string,
     *     time: string,
     *     duration: string,
     *     starts_at: string,
     *     ends_at: string,
     *     price: float,
     *     status: string,
     *     payment_status: string,
     *     qr_code: string|null,
     *     cancelled_at: string|null,
     *     created_at: string,
     *     activity: ActivityResource,
     *     slot: ActivitySlotResource
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'member_id' => (string) $this->member_id,
            'activity_id' => (string) $this->activity_id,
            'activity_slot_id' => (string) $this->activity_slot_id,
            'activity_title' => $this->activity?->title,
            'date' => $this->date->toDateString(),
            'time' => Carbon::createFromFormat('H:i:s', $this->starts_at)->format('H:i'),
            'duration' => Carbon::createFromFormat('H:i:s', $this->starts_at)->diffInMinutes(Carbon::createFromFormat('H:i:s', $this->ends_at)).' min',
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
