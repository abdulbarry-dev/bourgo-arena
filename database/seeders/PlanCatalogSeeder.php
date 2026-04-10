<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Monthly',
                'price' => 89.000,
                'duration_days' => 30,
                'included_services' => ['gym'],
                'has_all_courses' => false,
                'is_archived' => false,
            ],
            [
                'name' => 'Performance Monthly',
                'price' => 129.000,
                'duration_days' => 30,
                'included_services' => ['gym', 'classes'],
                'has_all_courses' => false,
                'is_archived' => false,
            ],
            [
                'name' => 'Quarterly Plus',
                'price' => 349.000,
                'duration_days' => 90,
                'included_services' => ['gym', 'classes', 'tennis'],
                'has_all_courses' => false,
                'is_archived' => false,
            ],
            [
                'name' => 'Annual Elite',
                'price' => 1199.000,
                'duration_days' => 365,
                'included_services' => ['gym', 'classes', 'tennis', 'squash'],
                'has_all_courses' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'Legacy Promo',
                'price' => 75.000,
                'duration_days' => 30,
                'included_services' => ['gym'],
                'has_all_courses' => false,
                'is_archived' => true,
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
