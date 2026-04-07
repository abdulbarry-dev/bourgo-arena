<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Course::create([
            'name' => 'Advanced Yoga',
            'instructor' => 'Jane Smith',
            'description' => 'A complex flow for experienced practitioners.',
            'color' => '#8b5cf6', // purple
        ]);

        \App\Models\Course::create([
            'name' => 'Morning Pilates',
            'instructor' => 'John Doe',
            'description' => 'Start your day right with a calm stretching session.',
            'color' => '#ec4899', // pink
        ]);

        \App\Models\Course::create([
            'name' => 'High Intensity Interval Training (HIIT)',
            'instructor' => 'Mike Johnson',
            'description' => 'Max heart-rate exercises back-to-back.',
            'color' => '#ef4444', // red
        ]);
        
        \App\Models\Course::create([
            'name' => 'Zumba Basics',
            'instructor' => 'Maria Garcia',
            'description' => 'Dance fitness program for all levels.',
            'color' => '#eab308', // yellow
        ]);
    }
}
