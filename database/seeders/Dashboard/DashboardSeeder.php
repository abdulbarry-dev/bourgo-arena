<?php

namespace Database\Seeders\Dashboard;

use Database\Seeders\Dashboard\Activities\ActivitySeeder;
use Database\Seeders\Dashboard\Activities\ActivitySlotSeeder;
use Database\Seeders\Dashboard\Analytics\RevenueSnapshotSeeder;
use Database\Seeders\Dashboard\Bookings\BookingSeeder;
use Database\Seeders\Dashboard\Bookings\CourtSlotSeeder;
use Database\Seeders\Dashboard\Catalog\CourseSeeder;
use Database\Seeders\Dashboard\Catalog\CourseSessionExceptionSeeder;
use Database\Seeders\Dashboard\Catalog\CourseSessionSeeder;
use Database\Seeders\Dashboard\Catalog\PlanCatalogSeeder;
use Database\Seeders\Dashboard\Catalog\SubscriptionAuditLogSeeder;
use Database\Seeders\Dashboard\Catalog\SubscriptionSeeder;
use Database\Seeders\Dashboard\Events\EventMatchSeeder;
use Database\Seeders\Dashboard\Events\EventParticipantSeeder;
use Database\Seeders\Dashboard\Events\EventSeeder;
use Database\Seeders\Dashboard\Members\LoyaltyPointSeeder;
use Database\Seeders\Dashboard\Members\MemberDeviceTokenSeeder;
use Database\Seeders\Dashboard\Members\MemberNotificationSeeder;
use Database\Seeders\Dashboard\Members\MemberOnboardingTokenSeeder;
use Database\Seeders\Dashboard\Members\MemberSeeder;
use Database\Seeders\Dashboard\Payments\PaymentReconciliationSeeder;
use Database\Seeders\Dashboard\Payments\PaymentSeeder;
use Database\Seeders\Dashboard\Reservations\ReservationSeeder;
use Database\Seeders\Dashboard\Users\AdminUserSeeder;
use Database\Seeders\Dashboard\Users\ManagerUserSeeder;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ManagerUserSeeder::class,
            MemberSeeder::class,
            LoyaltyPointSeeder::class,
            MemberDeviceTokenSeeder::class,
            MemberNotificationSeeder::class,
            MemberOnboardingTokenSeeder::class,
            PlanCatalogSeeder::class,
            CourseSeeder::class,
            CourseSessionSeeder::class,
            CourseSessionExceptionSeeder::class,
            SubscriptionSeeder::class,
            SubscriptionAuditLogSeeder::class,
            ActivitySeeder::class,
            ActivitySlotSeeder::class,
            CourtSlotSeeder::class,
            BookingSeeder::class,
            ReservationSeeder::class,
            PaymentSeeder::class,
            PaymentReconciliationSeeder::class,
            EventSeeder::class,
            EventParticipantSeeder::class,
            EventMatchSeeder::class,
            RevenueSnapshotSeeder::class,
        ]);
    }
}
