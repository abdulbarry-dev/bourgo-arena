<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive family account data:
 * - Parent-child member relationships
 * - Family subscriptions with shared access
 * - Multiple children per parent account
 */
class ComprehensiveFamilyMembersSeeder extends Seeder
{
    private array $maleFirstNames = [
        'Ahmed', 'Ali', 'Mohamed', 'Hassan', 'Omar', 'Karim', 'Fatih', 'Bilal',
    ];

    private array $femaleFirstNames = [
        'Fatima', 'Aisha', 'Zainab', 'Noor', 'Layla', 'Hana', 'Sara', 'Maryam',
    ];

    private array $lastNames = [
        'Ben Saad', 'Nasri', 'Toumi', 'Hakim', 'Boudaya', 'Medjahed', 'Zouari',
    ];

    private array $maleAvatars = [
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop',
    ];

    private array $femaleAvatars = [
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517849845537-1d51a20414de?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop',
    ];

    public function run(): void
    {
        // Get active members to use as parents
        $parentMembers = Member::query()
            ->where('status', 'active')
            ->where('state', 'active')
            ->whereHas('activeSubscription')
            ->get()
            ->take(10);

        foreach ($parentMembers as $parent) {
            // Create 1-3 child accounts per parent
            $childCount = random_int(1, 3);

            for ($i = 0; $i < $childCount; $i++) {
                $childGender = fake()->randomElement(['male', 'female']);
                $firstName = $childGender === 'male'
                    ? fake()->randomElement($this->maleFirstNames)
                    : fake()->randomElement($this->femaleFirstNames);
                $lastName = fake()->randomElement($this->lastNames);

                $child = Member::create([
                    'parent_id' => $parent->id,
                    'name' => "$firstName $lastName",
                    'email' => fake()->unique()->safeEmail(),
                    'phone' => fake()->unique()->numerify('2#######'),
                    'date_of_birth' => fake()->dateTimeBetween('-17 years', '-6 years')->format('Y-m-d'),
                    'gender' => $childGender,
                    'emergency_contact' => $parent->phone,
                    'avatar' => $childGender === 'male'
                        ? fake()->randomElement($this->maleAvatars)
                        : fake()->randomElement($this->femaleAvatars),
                    'status' => 'active',
                    'state' => 'active',
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'onboarding_completed_at' => now(),
                    'rgpd_consented_at' => now(),
                    'password' => 'hashed_password',
                    'is_family_account' => true,
                ]);

                // Optional: Create a subscription for the child (shares parent's plan)
                if (fake()->boolean(70)) {
                    $parentSubscription = $parent->activeSubscription;
                    if ($parentSubscription) {
                        // Child inherits similar subscription terms
                        $child->subscriptions()->create([
                            'plan_id' => $parentSubscription->plan_id,
                            'status' => 'active',
                            'starts_at' => $parentSubscription->starts_at,
                            'ends_at' => $parentSubscription->ends_at,
                            'payment_method' => $parentSubscription->payment_method,
                            'amount_paid' => 0, // Shared with parent
                            'enrolled_by' => $parentSubscription->enrolled_by,
                        ]);
                    }
                }
            }
        }
    }
}
