<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Comprehensive application data seeder for testing all endpoints
 * with realistic data including online image URLs.
 *
 * This seeder creates:
 * - Members in various states (active, pending, suspended, expired)
 * - Subscriptions with different plans and statuses
 * - Courses with image URLs and scheduled sessions
 * - Activities with time slots and online images
 * - Bookings with different statuses
 * - Check-in events with various results
 * - Terminals with online/offline states
 * - Device tokens for push notifications
 * - Family accounts and relationships
 * - Loyalty points and tier data
 * - Notifications with different types
 */
class ComprehensiveAppDataSeeder extends Seeder
{
    public function run(): void
    {
        // Foundation: Plans must exist first
        $this->call([
            PlanCatalogSeeder::class,
        ]);

        // Users & Admin Infrastructure
        $this->call([
            ManagerUserSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Terminals
        $this->call([
            HikvisionTerminalSeeder::class,
        ]);

        // Comprehensive Member Data with Subscriptions
        $this->call([
            ComprehensiveMembersSeeder::class,
        ]);

        // Course Management with Sessions
        $this->call([
            ComprehensiveCoursesSeeder::class,
        ]);

        // Course-Plan Relationships
        $this->call([
            CoursePlanSeeder::class,
        ]);

        // Activities & Time Slots (for reservation system)
        $this->call([
            ComprehensiveActivitiesSeeder::class,
        ]);

        // Bookings & Reservations
        $this->call([
            ComprehensiveBookingsSeeder::class,
        ]);

        // Check-in History
        $this->call([
            ComprehensiveCheckInEventsSeeder::class,
        ]);

        // Device Tokens for Push Notifications
        $this->call([
            ComprehensiveDeviceTokensSeeder::class,
        ]);

        // Family Accounts
        $this->call([
            ComprehensiveFamilyMembersSeeder::class,
        ]);

        // Member Notifications
        $this->call([
            ComprehensiveNotificationsSeeder::class,
        ]);

        // Loyalty Points & Tiers
        $this->call([
            ComprehensiveLoyaltySeeder::class,
        ]);
    }
}
