<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ActivityResource extends JsonResource
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
            'title' => $this->title,
            'name' => $this->title, // Keep for backward compatibility/tests
            'category' => $this->category,
            'base_price' => (float) $this->base_price,
            'currency' => $this->currency,
            'image_url' => $this->image_url ? (Str::startsWith($this->image_url, 'http') ? $this->image_url : asset('storage/'.$this->image_url)) : null,
            'images' => $images,
            'icon' => $this->icon,
            'description' => $this->description,
            'features' => $this->features,
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
        ];
    }
}
