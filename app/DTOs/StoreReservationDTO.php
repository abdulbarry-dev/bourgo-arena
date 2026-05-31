<?php

namespace App\DTOs;

class StoreReservationDTO
{
    public function __construct(
        public readonly int $activityId,
        public readonly int $activitySlotId,
        public readonly string $date,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            activityId: (int) $data['activity_id'],
            activitySlotId: (int) $data['activity_slot_id'],
            date: $data['date'],
        );
    }

    public function toArray(): array
    {
        return [
            'activity_id' => $this->activityId,
            'activity_slot_id' => $this->activitySlotId,
            'date' => $this->date,
        ];
    }
}
