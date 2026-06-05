<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => collect($this->images)->map(fn ($path) => Str::startsWith($path, 'http') ? $path : asset('storage/'.$path)),
            'format' => $this->format,
            'max_participants' => $this->max_participants,
            'participants_count' => $this->whenCounted('participants'),
            'registration_deadline' => $this->registration_deadline,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'requires_check_in' => $this->requires_check_in,
            'created_at' => $this->created_at,
        ];
    }
}
