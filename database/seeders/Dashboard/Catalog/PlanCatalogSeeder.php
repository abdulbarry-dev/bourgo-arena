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
                'included_services' => ['gym'],
                'has_all_courses' => false,
                'is_archived' => false,
                'service_id' => $fitnessService?->id,
            ],
            [
                'name' => 'Performance Monthly',
                'price' => 129.000,
                'duration_days' => 30,
                'included_services' => ['gym', 'classes'],
                'has_all_courses' => false,
                'is_archived' => false,
                'service_id' => $fitnessService?->id,
            ],
            [
                'name' => 'Quarterly Plus',
                'price' => 349.000,
                'duration_days' => 90,
                'included_services' => ['gym', 'classes', 'tennis'],
                'has_all_courses' => false,
                'is_archived' => false,
                'service_id' => $tennisService?->id,
            ],
            [
                'name' => 'Annual Elite',
                'price' => 1199.000,
                'duration_days' => 365,
                'included_services' => ['gym', 'classes', 'tennis', 'squash'],
                'has_all_courses' => true,
                'is_archived' => false,
                'service_id' => $padelService?->id,
            ],
            [
                'name' => 'Legacy Promo',
                'price' => 75.000,
                'duration_days' => 30,
                'included_services' => ['gym'],
                'has_all_courses' => false,
                'is_archived' => true,
                'service_id' => $fitnessService?->id,
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
