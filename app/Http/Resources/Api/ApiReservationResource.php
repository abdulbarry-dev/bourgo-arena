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
            /** @description The unique identifier of the reservation. @example 1 */
            'id' => $this->id,
            /** @description The ID of the member who made the reservation. @example 1 */
            'member_id' => $this->member_id,
            /** @description The ID of the associated activity. @example 1 */
            'activity_id' => $this->activity_id,
            /** @description The ID of the specific activity slot. @example 1 */
            'activity_slot_id' => $this->activity_slot_id,
            /** @description The title of the activity. @example "Padel Match" */
            'activity_title' => $this->activity?->title,
            /** @description The date of the reservation. @format date @example "2024-05-20" */
            'date' => $this->date->toDateString(),
            /** @description The start time formatted for display. @example "14:00" */
            'time' => Carbon::createFromFormat('H:i:s', $this->starts_at)->format('H:i'),
            /** @description The duration of the session in minutes. @example "60 min" */
            'duration' => Carbon::createFromFormat('H:i:s', $this->starts_at)->diffInMinutes(Carbon::createFromFormat('H:i:s', $this->ends_at)).' min',
            /** @description The exact start time. @format time @example "14:00:00" */
            'starts_at' => $this->starts_at,
            /** @description The exact end time. @format time @example "15:00:00" */
            'ends_at' => $this->ends_at,
            /** @description The price paid or due for the reservation. @example 45.0 */
            'price' => (float) $this->price,
            /** @description The current status of the reservation. @example "confirmed" */
            'status' => $this->status,
            /** @description The payment status. @example "paid" */
            'payment_status' => $this->payment_status,
            /** @description The QR code content for check-in. @example "RES-123456" */
            'qr_code' => $this->qr_code,
            /** @description When the reservation was cancelled, if applicable. @format date-time @example "2024-05-19 10:00:00" */
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            /** @description When the reservation was created. @format date-time @example "2024-05-18 15:30:00" */
            'created_at' => $this->created_at->toDateTimeString(),
            /** @description The associated activity details. */
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            /** @description The specific slot details. */
            'slot' => new ActivitySlotResource($this->whenLoaded('slot')),
        ];
    }
}
