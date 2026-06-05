<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Fitness & Gym',
                'slug' => 'fitness-gym',
                'description' => 'Full access to our state-of-the-art gym and fitness equipment.',
                'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&q=80&w=1470',
                'status' => 'active',
            ],
            [
                'name' => 'Padel Courts',
                'slug' => 'padel-courts',
                'description' => 'Premium padel courts available for booking and competitive play.',
                'image_url' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&q=80&w=1470',
                'status' => 'active',
            ],
            [
                'name' => 'Tennis Academy',
                'slug' => 'tennis-academy',
                'description' => 'Professional tennis coaching and court rentals for all skill levels.',
                'image_url' => 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&q=80&w=1470',
                'status' => 'active',
            ],
            [
                'name' => 'Wellness Center',
                'slug' => 'wellness-center',
                'description' => 'Relax and recover with our spa, sauna, and specialized wellness programs.',
                'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=80&w=1470',
                'status' => 'active',
            ],
        ];

        foreach ($services as $serviceData) {
            Service::query()->updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }
    }
}
