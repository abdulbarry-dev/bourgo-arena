<?php

namespace Database\Seeders\Staging;

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\ActivitySlot;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Plan;
use App\Models\Service;
use Illuminate\Database\Seeder;

class BulkCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedExtraServices();
        $this->seedExtraPlans();
        $this->seedExtraCourses();
        $this->seedExtraActivities();

        $this->command?->info(sprintf(
            '  Catalog: %d services, %d plans, %d courses, %d activities',
            Service::count(), Plan::withoutGlobalScopes()->count(), Course::count(), Activity::count()
        ));
    }

    private function seedExtraServices(): void
    {
        $extras = [
            ['name' => 'Swimming Pool', 'slug' => 'swimming-pool', 'description' => 'Olympic-size indoor pool with lanes for recreational and competitive swimmers.', 'status' => 'active'],
            ['name' => 'Squash Courts', 'slug' => 'squash-courts', 'description' => 'Four regulation squash courts available for booking and leagues.', 'status' => 'active'],
            ['name' => 'Boxing & Martial Arts', 'slug' => 'boxing-martial-arts', 'description' => 'Full-equipped boxing ring and dojo for martial arts training.', 'status' => 'active'],
            ['name' => 'Cycling Studio', 'slug' => 'cycling-studio', 'description' => 'Indoor cycling studio with smart bikes and virtual courses.', 'status' => 'active'],
            ['name' => 'Basketball Court', 'slug' => 'basketball-court', 'description' => 'Full-size indoor basketball court available for open play and leagues.', 'status' => 'active'],
            ['name' => 'Kids Zone', 'slug' => 'kids-zone', 'description' => 'Supervised activity zone for children aged 4–14.', 'status' => 'active'],
        ];

        foreach ($extras as $data) {
            Service::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }

    private function seedExtraPlans(): void
    {
        $services = Service::all()->keyBy('slug');

        $plans = [
            // Swimming
            ['name' => 'Swim Starter', 'price' => 79.000, 'duration_days' => 30, 'slug' => 'swimming-pool'],
            ['name' => 'Swim Performance', 'price' => 119.000, 'duration_days' => 30, 'slug' => 'swimming-pool'],
            ['name' => 'Swim Annual', 'price' => 999.000, 'duration_days' => 365, 'has_all_courses' => true, 'slug' => 'swimming-pool'],
            // Squash
            ['name' => 'Squash Monthly', 'price' => 95.000, 'duration_days' => 30, 'slug' => 'squash-courts'],
            ['name' => 'Squash Quarterly', 'price' => 260.000, 'duration_days' => 90, 'slug' => 'squash-courts'],
            // Boxing
            ['name' => 'Boxing Basic', 'price' => 85.000, 'duration_days' => 30, 'slug' => 'boxing-martial-arts'],
            ['name' => 'Boxing Pro', 'price' => 145.000, 'duration_days' => 30, 'slug' => 'boxing-martial-arts'],
            ['name' => 'Boxing Semi-Annual', 'price' => 799.000, 'duration_days' => 180, 'has_all_courses' => true, 'slug' => 'boxing-martial-arts'],
            // Cycling
            ['name' => 'Cycling Monthly', 'price' => 69.000, 'duration_days' => 30, 'slug' => 'cycling-studio'],
            ['name' => 'Cycling Quarterly', 'price' => 189.000, 'duration_days' => 90, 'slug' => 'cycling-studio'],
            // Basketball
            ['name' => 'Basketball Monthly', 'price' => 59.000, 'duration_days' => 30, 'slug' => 'basketball-court'],
            ['name' => 'Basketball Season', 'price' => 299.000, 'duration_days' => 180, 'slug' => 'basketball-court'],
            // Kids
            ['name' => 'Kids Monthly', 'price' => 55.000, 'duration_days' => 30, 'slug' => 'kids-zone'],
            ['name' => 'Kids Quarterly', 'price' => 145.000, 'duration_days' => 90, 'has_all_courses' => true, 'slug' => 'kids-zone'],
            // Premium bundles
            ['name' => 'All Access Monthly', 'price' => 249.000, 'duration_days' => 30, 'has_all_courses' => true, 'slug' => 'fitness-gym'],
            ['name' => 'All Access Annual', 'price' => 2499.000, 'duration_days' => 365, 'has_all_courses' => true, 'slug' => 'fitness-gym'],
        ];

        foreach ($plans as $data) {
            $serviceSlug = $data['slug'];
            unset($data['slug']);
            $service = $services[$serviceSlug] ?? null;

            if ($service) {
                $data['service_id'] = $service->id;
                Plan::updateOrCreate(['name' => $data['name']], $data);
            }
        }
    }

    private function seedExtraCourses(): void
    {
        $services = Service::all()->keyBy('slug');

        $courses = [
            // Fitness
            ['name' => 'Functional Strength', 'description' => 'Build real-world strength with compound lifts and bodyweight movements.', 'service_id' => $services['fitness-gym']?->id],
            ['name' => 'HIIT Cardio Blast', 'description' => 'High-intensity interval training to torch calories and boost endurance.', 'service_id' => $services['fitness-gym']?->id],
            ['name' => 'Body Composition', 'description' => 'Hybrid resistance and cardio program designed to reshape the body.', 'service_id' => $services['fitness-gym']?->id],
            ['name' => 'Mobility & Stretch', 'description' => 'Deep-tissue stretching and joint mobility work for injury prevention.', 'service_id' => $services['fitness-gym']?->id],
            // Tennis
            ['name' => 'Tennis Foundations', 'description' => 'Beginner-friendly tennis covering strokes, footwork, and scoring.', 'service_id' => $services['tennis-academy']?->id],
            ['name' => 'Competitive Tennis', 'description' => 'Match tactics, serve & volley strategy for tournament players.', 'service_id' => $services['tennis-academy']?->id],
            ['name' => 'Junior Tennis', 'description' => 'Ages 8–16 junior development program with certified coaches.', 'service_id' => $services['tennis-academy']?->id],
            // Padel
            ['name' => 'Padel Basics', 'description' => 'Introduction to padel — rules, positioning, and wall play.', 'service_id' => $services['padel-courts']?->id],
            ['name' => 'Padel Advanced', 'description' => 'Advanced padel strategy, lob control, and doubles coordination.', 'service_id' => $services['padel-courts']?->id],
            // Swimming
            ['name' => 'Learn to Swim', 'description' => 'Adult beginner swimming lessons with certified instructors.', 'service_id' => $services['swimming-pool']?->id],
            ['name' => 'Competitive Swimming', 'description' => 'Stroke efficiency and race-pace training for competitive swimmers.', 'service_id' => $services['swimming-pool']?->id],
            ['name' => 'Aqua Aerobics', 'description' => 'Low-impact water aerobics for all fitness levels.', 'service_id' => $services['swimming-pool']?->id],
            // Boxing
            ['name' => 'Boxing Fundamentals', 'description' => 'Stance, jab, cross, hook, and uppercut with bag work.', 'service_id' => $services['boxing-martial-arts']?->id],
            ['name' => 'Muay Thai', 'description' => 'Traditional Thai boxing incorporating eight limbs of combat.', 'service_id' => $services['boxing-martial-arts']?->id],
            ['name' => 'Kickboxing Cardio', 'description' => 'Non-contact kickboxing for fitness, stress relief, and conditioning.', 'service_id' => $services['boxing-martial-arts']?->id],
            // Cycling
            ['name' => 'Spin Circuit', 'description' => 'Guided indoor cycling sessions with heart-rate zone training.', 'service_id' => $services['cycling-studio']?->id],
            ['name' => 'Endurance Ride', 'description' => 'Long steady-state cycling sessions to build aerobic base.', 'service_id' => $services['cycling-studio']?->id],
            // Kids
            ['name' => 'Kids Gymnastics', 'description' => 'Fun gymnastics program developing agility, balance, and coordination.', 'service_id' => $services['kids-zone']?->id],
            ['name' => 'Kids Football', 'description' => 'Weekly football training for children aged 6–14.', 'service_id' => $services['kids-zone']?->id],
            ['name' => 'Multi-Sport Camp', 'description' => 'Rotating multi-sport program introducing kids to 6+ disciplines.', 'service_id' => $services['kids-zone']?->id],
        ];

        $startTimes = ['07:00', '08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
        $sessionStartsAt = now()->startOfWeek()->subWeeks(2)->toDateString();
        $sessionEndsAt = now()->addMonths(6)->toDateString();

        foreach ($courses as $data) {
            if (! $data['service_id']) {
                continue;
            }

            $data['status'] = 'active';
            $course = Course::updateOrCreate(['name' => $data['name']], $data);

            $sessionCount = rand(2, 4);
            $usedDays = [];

            for ($s = 0; $s < $sessionCount; $s++) {
                do {
                    $day = rand(0, 6);
                } while (in_array($day, $usedDays) && count($usedDays) < 7);
                $usedDays[] = $day;

                $alreadyExists = CourseSession::where('course_id', $course->id)->where('day_of_week', $day)->exists();

                if (! $alreadyExists) {
                    CourseSession::create([
                        'course_id' => $course->id,
                        'day_of_week' => $day,
                        'starts_at' => $startTimes[array_rand($startTimes)],
                        'starts_at_date' => $sessionStartsAt,
                        'ends_at_date' => $sessionEndsAt,
                        'duration_minutes' => [45, 60, 75, 90][rand(0, 3)],
                        'capacity' => rand(8, 30),
                        'is_cancelled' => false,
                    ]);
                }
            }
        }
    }

    private function seedExtraActivities(): void
    {
        $services = Service::all()->keyBy('slug');

        $activities = [
            ['title' => 'Tennis Court B', 'base_price' => 25.000, 'capacity' => 4, 'service_id' => $services['tennis-academy']?->id],
            ['title' => 'Tennis Court C', 'base_price' => 25.000, 'capacity' => 4, 'service_id' => $services['tennis-academy']?->id],
            ['title' => 'Padel Court 3', 'base_price' => 30.000, 'capacity' => 4, 'service_id' => $services['padel-courts']?->id],
            ['title' => 'Padel Court 4', 'base_price' => 30.000, 'capacity' => 4, 'service_id' => $services['padel-courts']?->id],
            ['title' => 'Padel Court 5', 'base_price' => 35.000, 'capacity' => 4, 'service_id' => $services['padel-courts']?->id],
            ['title' => 'Squash Court A', 'base_price' => 20.000, 'capacity' => 2, 'service_id' => $services['squash-courts']?->id],
            ['title' => 'Squash Court B', 'base_price' => 20.000, 'capacity' => 2, 'service_id' => $services['squash-courts']?->id],
            ['title' => 'Swimming Lane Booking', 'base_price' => 15.000, 'capacity' => 1, 'service_id' => $services['swimming-pool']?->id],
            ['title' => 'Personal Training Session', 'base_price' => 60.000, 'capacity' => 1, 'service_id' => $services['fitness-gym']?->id],
            ['title' => 'Boxing 1-on-1 Session', 'base_price' => 55.000, 'capacity' => 1, 'service_id' => $services['boxing-martial-arts']?->id],
            ['title' => 'Basketball Court Booking', 'base_price' => 40.000, 'capacity' => 10, 'service_id' => $services['basketball-court']?->id],
            ['title' => 'Kids Party Package', 'base_price' => 120.000, 'capacity' => 15, 'service_id' => $services['kids-zone']?->id],
            ['title' => 'Wellness Massage', 'base_price' => 75.000, 'capacity' => 1, 'service_id' => $services['wellness-center']?->id],
            ['title' => 'Sauna Session', 'base_price' => 25.000, 'capacity' => 4, 'service_id' => $services['wellness-center']?->id],
            ['title' => 'Cycling Studio Session', 'base_price' => 18.000, 'capacity' => 20, 'service_id' => $services['cycling-studio']?->id],
        ];

        $days = range(0, 6);
        $times = ['07:00', '09:00', '11:00', '14:00', '16:00', '18:00', '20:00'];
        $activitySessionStartsAt = now()->startOfWeek()->subWeeks(2)->toDateString();
        $activitySessionEndsAt = now()->addMonths(6)->toDateString();

        foreach ($activities as $data) {
            if (! $data['service_id']) {
                continue;
            }

            $data['is_active'] = true;
            $activity = Activity::updateOrCreate(['title' => $data['title']], $data);

            if ($activity->wasRecentlyCreated) {
                foreach ($days as $day) {
                    $slotCount = rand(1, 3);
                    $usedTimes = [];

                    for ($t = 0; $t < $slotCount; $t++) {
                        $time = $times[array_rand($times)];
                        if (in_array($time, $usedTimes)) {
                            continue;
                        }
                        $usedTimes[] = $time;

                        ActivitySession::create([
                            'activity_id' => $activity->id,
                            'day_of_week' => $day,
                            'starts_at' => $time,
                            'starts_at_date' => $activitySessionStartsAt,
                            'ends_at_date' => $activitySessionEndsAt,
                            'duration_minutes' => rand(1, 3) * 30,
                            'is_cancelled' => false,
                        ]);
                    }
                }

                $slotCount = rand(3, 8);
                for ($s = 0; $s < $slotCount; $s++) {
                    $startsAt = now()->addDays(rand(1, 30))->setHour(rand(7, 20))->setMinute(0)->setSecond(0);
                    $endsAt = $startsAt->copy()->addMinutes(rand(1, 3) * 30);
                    ActivitySlot::updateOrCreate(
                        ['activity_id' => $activity->id, 'starts_at' => $startsAt, 'ends_at' => $endsAt],
                        ['capacity' => $activity->capacity, 'is_available' => true],
                    );
                }
            }
        }
    }
}
