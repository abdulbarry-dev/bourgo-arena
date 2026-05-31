<?php

namespace Database\Seeders\Dashboard\Events;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class EventParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $summerEvent = Event::query()->where('name', 'Summer Padel Cup')->first();
        $autumnEvent = Event::query()->where('name', 'Autumn Tennis Ladder')->first();

        if (! $summerEvent || ! $autumnEvent) {
            return;
        }

        $summerParticipants = [
            ['name' => 'Nora Haddad', 'email' => 'nora.haddad@example.com', 'phone' => '200000001', 'seed_number' => 1, 'status' => 'approved', 'has_checked_in' => true],
            ['name' => 'Youssef Ben Salem', 'email' => 'youssef.bensalem@example.com', 'phone' => '200000002', 'seed_number' => 2, 'status' => 'approved', 'has_checked_in' => false],
            ['name' => 'Meriem Fassi', 'email' => 'meriem.fassi@example.com', 'phone' => '200000003', 'seed_number' => 3, 'status' => 'approved', 'has_checked_in' => false],
            ['name' => 'Rami Cherif', 'email' => 'rami.cherif@example.com', 'phone' => '200000004', 'seed_number' => 4, 'status' => 'approved', 'has_checked_in' => false],
        ];

        $autumnParticipants = [
            ['name' => 'Sara Mansouri', 'email' => 'sara.mansouri@example.com', 'phone' => '200000005', 'seed_number' => 1, 'status' => 'approved', 'has_checked_in' => false],
            ['name' => 'Adam Khelifi', 'email' => 'adam.khelifi@example.com', 'phone' => '200000006', 'seed_number' => 2, 'status' => 'approved', 'has_checked_in' => false],
            ['name' => 'Ines Jaziri', 'email' => 'ines.jaziri@example.com', 'phone' => '200000007', 'seed_number' => null, 'status' => 'waitlisted', 'has_checked_in' => false],
            ['name' => 'Omar Toumi', 'email' => 'omar.toumi@example.com', 'phone' => '200000008', 'seed_number' => null, 'status' => 'pending', 'has_checked_in' => false],
        ];

        foreach (array_merge($summerParticipants, $autumnParticipants) as $participantData) {
            $user = User::query()->updateOrCreate(
                ['email' => $participantData['email']],
                [
                    'name' => $participantData['name'],
                    'phone' => $participantData['phone'],
                    'password' => 'Test@12345',
                    'role' => UserRole::Member,
                    'email_verified_at' => now(),
                ],
            );

            $event = str_contains($participantData['email'], 'nora') || str_contains($participantData['email'], 'youssef') || str_contains($participantData['email'], 'meriem') || str_contains($participantData['email'], 'rami')
                ? $summerEvent
                : $autumnEvent;

            EventParticipant::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                ],
                [
                    'seed_number' => $participantData['seed_number'],
                    'has_checked_in' => $participantData['has_checked_in'],
                    'status' => $participantData['status'],
                    'withdrawn_at' => null,
                ],
            );
        }
    }
}
