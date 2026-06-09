<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\ActivitySessionResource;
use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class ApiReservationResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        $session = $this->whenLoaded('session');

        return [
            'id' => (string) $this->id,
            'member_id' => (string) $this->member_id,
            'activity_id' => (string) $this->activity_id,
            'activity_session_id' => (string) $this->activity_session_id,
            'activity_title' => $this->activity?->title,
            'date' => $this->date->toDateString(),
            'session' => new ActivitySessionResource($session),
            'price' => (float) $this->price,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'qr_code' => $this->qr_code,
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
        ];
    }
}
