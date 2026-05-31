<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\CourseSession;
use App\Models\CourseSessionException;
use Illuminate\Database\Seeder;

class CourseSessionExceptionSeeder extends Seeder
{
    public function run(): void
    {
        $session = CourseSession::query()
            ->whereHas('course', function ($query): void {
                $query->where('name', 'Tennis Technique');
            })
            ->where('day_of_week', 2)
            ->first();

        if ($session === null) {
            return;
        }

        CourseSessionException::query()->updateOrCreate(
            [
                'course_session_id' => $session->id,
                'date' => now()->startOfWeek()->addDays(2)->toDateString(),
            ],
            [
                'is_cancelled' => true,
            ],
        );
    }
}
