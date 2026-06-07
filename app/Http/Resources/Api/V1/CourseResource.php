<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = collect($this->images ?? [])
            ->whenEmpty(fn ($c) => $this->image_url ? collect([$this->image_url]) : $c)
            ->values()
            ->map(fn ($p) => Str::startsWith($p, 'http') ? $p : asset('storage/'.$p))
            ->toArray();

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => $images,
            'image_url' => $this->image_url ? (Str::startsWith($this->image_url, 'http') ? $this->image_url : asset('storage/'.$this->image_url)) : null,
            'status' => $this->status,
        ];
    }
}
