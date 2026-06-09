<?php

namespace App\DTOs;

class StoreReservationDTO
{
    public function __construct(
        public readonly int $activityId,
        public readonly int $activitySessionId,
        public readonly string $date,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            activityId: (int) $data['activity_id'],
            activitySessionId: (int) $data['activity_session_id'],
            date: $data['date'],
        );
    }

    public function toArray(): array
    {
        return [
            'activity_id' => $this->activityId,
            'activity_session_id' => $this->activitySessionId,
            'date' => $this->date,
        ];
    }
}
