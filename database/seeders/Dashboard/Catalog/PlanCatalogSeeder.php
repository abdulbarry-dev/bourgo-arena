<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Plan;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PlanCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fitnessService = Service::where('slug', 'fitness-gym')->first();
        $tennisService = Service::where('slug', 'tennis-academy')->first();
        $padelService = Service::where('slug', 'padel-courts')->first();

        $plans = [
            [
                'name' => 'Starter Monthly',
                'price' => 89.000,
                'duration_days' => 30,
                'has_all_courses' => false,
                'is_archived' => false,
                'service_id' => $fitnessService?->id,
                'description' => 'A perfect introductory plan to jumpstart your fitness journey. Get access to our gym facilities during off-peak hours with basic support from our floor trainers.',
                'image_url' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&q=80&w=1470',
            ],
            [
                'name' => 'Performance Monthly',
                'price' => 129.000,
                'duration_days' => 30,
                'has_all_courses' => false,
                'is_archived' => false,
                'service_id' => $fitnessService?->id,
                'description' => 'Unleash your potential with full access to all our gym zones, group fitness classes, and advanced performance tracking amenities. Perfect for dedicated athletes.',
                'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&q=80&w=1470',
            ],
            [
                'name' => 'Quarterly Plus',
                'price' => 349.000,
                'duration_days' => 90,
                'has_all_courses' => false,
                'is_archived' => false,
                'service_id' => $tennisService?->id,
                'description' => 'Commit to a full quarter of athletic development. Includes priority court bookings, access to select coaching clinics, and unlimited gym use.',
                'image_url' => 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&q=80&w=1470',
            ],
            [
                'name' => 'Annual Elite',
                'price' => 1199.000,
                'duration_days' => 365,
                'has_all_courses' => true,
                'is_archived' => false,
                'service_id' => $padelService?->id,
                'description' => 'The ultimate VIP experience. Enjoy unrestricted access to all facilities, complimentary entry to all courses, premium locker room privileges, and exclusive member events.',
                'image_url' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&q=80&w=1470',
            ],
            [
                'name' => 'Legacy Promo',
                'price' => 75.000,
                'duration_days' => 30,
                'has_all_courses' => false,
                'is_archived' => true,
                'service_id' => $fitnessService?->id,
                'description' => 'An archived promotional plan for early adopters.',
                'image_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&q=80&w=1470',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['name' => $plan['name']],
                $plan,
            );
        }
    }
}
