<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     description: string|null,
     *     images: list<string>,
     *     image_url: string|null,
     *     format: string,
     *     max_participants: int,
     *     participants_count?: int,
     *     registration_deadline: string|null,
     *     start_date: string|null,
     *     end_date: string|null,
     *     requires_check_in: bool,
     *     status: string,
     *     created_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $images = collect($this->images)->map(
            fn ($path) => Str::startsWith($path, 'http') ? $path : asset('storage/'.$path)
        );

        $imageUrl = $this->images
            ? with(collect($this->images)->first(), fn ($p) => Str::startsWith($p, 'http') ? $p : asset('storage/'.$p))
            : null;

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => $images,
            'image_url' => $imageUrl,
            'format' => $this->format,
            'max_participants' => $this->max_participants,
            'participants_count' => $this->whenCounted('participants'),
            'is_registered' => $this->when(
                $request->user(),
                fn () => $this->participants()->where('user_id', $request->user()->id)->exists()
            ),
            'registration_deadline' => $this->registration_deadline,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'requires_check_in' => $this->requires_check_in,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
