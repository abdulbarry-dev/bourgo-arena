<?php

namespace Database\Seeders\Staging;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventParticipant;
use App\Models\Service;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BulkEventsSeeder extends Seeder
{
    public function run(): void
    {
        if (Event::count() > 8) {
            $this->command?->info('  Events already seeded. Skipping.');

            return;
        }

        $services = Service::all()->keyBy('slug');

        $eventsData = $this->buildEventsData($services);

        foreach ($eventsData as $data) {
            ['event' => $eventData, 'participants' => $participantSpec, 'bracket' => $hasBracket] = $data;

            $event = Event::updateOrCreate(['name' => $eventData['name']], $eventData);

            if ($event->participants()->count() === 0 && $participantSpec > 0) {
                $this->seedParticipants($event, $participantSpec);
            }

            if ($hasBracket && $event->matches()->count() === 0) {
                $this->generateBracket($event);
            }
        }

        $this->command?->info(sprintf(
            '  Events: %d | Participants: %d | Matches: %d',
            Event::count(), EventParticipant::count(), EventMatch::count()
        ));
    }

    private function buildEventsData($services): array
    {
        $padel = $services['padel-courts'] ?? null;
        $tennis = $services['tennis-academy'] ?? null;
        $fitness = $services['fitness-gym'] ?? null;
        $pool = $services['swimming-pool'] ?? null;
        $boxing = $services['boxing-martial-arts'] ?? null;
        $bball = $services['basketball-court'] ?? null;
        $kids = $services['kids-zone'] ?? null;

        return [
            // ── COMPLETED events (past, with results) ──────────────────────────
            [
                'event' => [
                    'name' => 'Spring Padel Championship 2026',
                    'description' => 'Annual doubles padel championship — completed with full bracket results.',
                    'format' => '2v2',
                    'max_participants' => 8,
                    'registration_deadline' => now()->subDays(50),
                    'start_date' => now()->subDays(44),
                    'end_date' => now()->subDays(42),
                    'requires_check_in' => true,
                    'service_id' => $padel?->id,
                    'created_at' => now()->subDays(75),
                ],
                'participants' => 8,
                'bracket' => true,
            ],
            [
                'event' => [
                    'name' => 'January Tennis Open 2026',
                    'description' => 'Singles ladder tournament — completed.',
                    'format' => '1v1',
                    'max_participants' => 8,
                    'registration_deadline' => now()->subDays(38),
                    'start_date' => now()->subDays(32),
                    'end_date' => now()->subDays(28),
                    'requires_check_in' => false,
                    'service_id' => $tennis?->id,
                    'created_at' => now()->subDays(60),
                ],
                'participants' => 8,
                'bracket' => true,
            ],
            [
                'event' => [
                    'name' => 'Ramadan Fitness Challenge',
                    'description' => 'Group fitness challenge completed during Ramadan.',
                    'format' => 'group',
                    'max_participants' => 50,
                    'registration_deadline' => now()->subDays(25),
                    'start_date' => now()->subDays(20),
                    'end_date' => now()->subDays(10),
                    'requires_check_in' => true,
                    'service_id' => $fitness?->id,
                    'created_at' => now()->subDays(45),
                ],
                'participants' => 38,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Winter Squash League Season 1',
                    'description' => 'Completed squash round-robin league.',
                    'format' => '1v1',
                    'max_participants' => 16,
                    'registration_deadline' => now()->subDays(70),
                    'start_date' => now()->subDays(65),
                    'end_date' => now()->subDays(55),
                    'requires_check_in' => false,
                    'service_id' => $padel?->id,
                    'created_at' => now()->subDays(90),
                ],
                'participants' => 16,
                'bracket' => true,
            ],
            [
                'event' => [
                    'name' => 'Kids Swimming Gala',
                    'description' => 'Junior swimming competition — completed.',
                    'format' => 'group',
                    'max_participants' => 30,
                    'registration_deadline' => now()->subDays(18),
                    'start_date' => now()->subDays(14),
                    'end_date' => now()->subDays(14),
                    'requires_check_in' => true,
                    'service_id' => $kids?->id ?? $fitness?->id,
                    'created_at' => now()->subDays(35),
                ],
                'participants' => 24,
                'bracket' => false,
            ],

            // ── IN PROGRESS events ─────────────────────────────────────────────
            [
                'event' => [
                    'name' => 'Summer Padel Cup 2026',
                    'description' => 'Doubles padel bracket — currently in progress.',
                    'format' => '2v2',
                    'max_participants' => 8,
                    'registration_deadline' => now()->subDays(3),
                    'start_date' => now()->subDays(1),
                    'end_date' => now()->addDays(1),
                    'requires_check_in' => true,
                    'service_id' => $padel?->id,
                    'created_at' => now()->subDays(20),
                ],
                'participants' => 8,
                'bracket' => true,
            ],
            [
                'event' => [
                    'name' => 'June Tennis Doubles Cup',
                    'description' => 'Doubles tennis tournament underway.',
                    'format' => '2v2',
                    'max_participants' => 8,
                    'registration_deadline' => now()->subDays(2),
                    'start_date' => now()->subDays(1),
                    'end_date' => now()->addDays(2),
                    'requires_check_in' => false,
                    'service_id' => $tennis?->id,
                    'created_at' => now()->subDays(18),
                ],
                'participants' => 8,
                'bracket' => false,
            ],

            // ── OPEN events (registration open) ───────────────────────────────
            [
                'event' => [
                    'name' => 'Autumn Tennis Ladder 2026',
                    'description' => 'Singles progressive ladder open for registration.',
                    'format' => '1v1',
                    'max_participants' => 12,
                    'registration_deadline' => now()->addDays(18),
                    'start_date' => now()->addDays(22),
                    'end_date' => now()->addDays(28),
                    'requires_check_in' => false,
                    'service_id' => $tennis?->id,
                    'created_at' => now()->subDays(5),
                ],
                'participants' => 7,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Community Fun Run — July Edition',
                    'description' => 'Non-competitive 5k run for all members. All fitness levels welcome.',
                    'format' => 'group',
                    'max_participants' => 100,
                    'registration_deadline' => now()->addDays(5),
                    'start_date' => now()->addDays(8),
                    'end_date' => now()->addDays(8),
                    'requires_check_in' => true,
                    'service_id' => $fitness?->id,
                    'created_at' => now()->subDays(7),
                ],
                'participants' => 42,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Boxing Tournament — Lightweight Division',
                    'description' => 'Amateur boxing tournament open to all club members.',
                    'format' => '1v1',
                    'max_participants' => 8,
                    'registration_deadline' => now()->addDays(12),
                    'start_date' => now()->addDays(16),
                    'end_date' => now()->addDays(16),
                    'requires_check_in' => true,
                    'service_id' => $boxing?->id ?? $fitness?->id,
                    'created_at' => now()->subDays(3),
                ],
                'participants' => 5,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Basketball 3v3 Summer League',
                    'description' => 'Outdoor 3-on-3 basketball league — 8 teams max.',
                    'format' => '5v5',
                    'max_participants' => 24,
                    'registration_deadline' => now()->addDays(20),
                    'start_date' => now()->addDays(25),
                    'end_date' => now()->addDays(55),
                    'requires_check_in' => false,
                    'service_id' => $bball?->id ?? $fitness?->id,
                    'created_at' => now()->subDays(2),
                ],
                'participants' => 12,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Padel Beginners Cup',
                    'description' => 'Open to players who have been members for less than 6 months.',
                    'format' => '2v2',
                    'max_participants' => 8,
                    'registration_deadline' => now()->addDays(7),
                    'start_date' => now()->addDays(10),
                    'end_date' => now()->addDays(11),
                    'requires_check_in' => true,
                    'service_id' => $padel?->id,
                    'created_at' => now()->subDays(1),
                ],
                'participants' => 6,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Cross-Training Endurance Challenge',
                    'description' => 'Timed multi-station fitness challenge — solo competition.',
                    'format' => '1v1',
                    'max_participants' => 32,
                    'registration_deadline' => now()->addDays(15),
                    'start_date' => now()->addDays(18),
                    'end_date' => now()->addDays(18),
                    'requires_check_in' => true,
                    'service_id' => $fitness?->id,
                    'created_at' => now()->subDays(2),
                ],
                'participants' => 14,
                'bracket' => false,
            ],

            // ── CANCELLED events ───────────────────────────────────────────────
            [
                'event' => [
                    'name' => 'Winter Padel Invitational (Cancelled)',
                    'description' => 'Cancelled due to facility maintenance.',
                    'format' => '2v2',
                    'max_participants' => 16,
                    'registration_deadline' => now()->subDays(15),
                    'start_date' => now()->subDays(10),
                    'end_date' => now()->subDays(8),
                    'requires_check_in' => false,
                    'canceled_at' => now()->subDays(12),
                    'service_id' => $padel?->id,
                    'created_at' => now()->subDays(35),
                ],
                'participants' => 6,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'Summer Swim Meet (Cancelled)',
                    'description' => 'Cancelled — insufficient registrations.',
                    'format' => 'group',
                    'max_participants' => 40,
                    'registration_deadline' => now()->subDays(8),
                    'start_date' => now()->subDays(4),
                    'end_date' => now()->subDays(4),
                    'requires_check_in' => true,
                    'canceled_at' => now()->subDays(6),
                    'service_id' => $pool?->id ?? $fitness?->id,
                    'created_at' => now()->subDays(25),
                ],
                'participants' => 8,
                'bracket' => false,
            ],

            // ── DRAFT events (no deadline set) ─────────────────────────────────
            [
                'event' => [
                    'name' => 'Q3 Padel Masters [Draft]',
                    'description' => 'Planning phase — dates TBD.',
                    'format' => '2v2',
                    'max_participants' => 16,
                    'service_id' => $padel?->id,
                    'created_at' => now()->subDays(1),
                ],
                'participants' => 0,
                'bracket' => false,
            ],
            [
                'event' => [
                    'name' => 'End of Year Gala Tournament [Draft]',
                    'description' => 'Multi-sport year-end celebration tournament.',
                    'format' => 'group',
                    'max_participants' => 100,
                    'service_id' => $fitness?->id,
                    'created_at' => now(),
                ],
                'participants' => 0,
                'bracket' => false,
            ],
        ];
    }

    private function seedParticipants(Event $event, int $count): void
    {
        $users = $this->participantUserPool($count);
        $isBracketReady = $event->start_date && $event->start_date->isPast();
        $seed = 1;

        foreach ($users as $user) {
            $status = $isBracketReady ? 'approved' : $this->participantStatus();

            EventParticipant::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $user->id],
                [
                    'seed_number' => $isBracketReady ? $seed++ : null,
                    'has_checked_in' => $isBracketReady && $event->requires_check_in && rand(0, 1),
                    'status' => $status,
                    'created_at' => $event->created_at ?? now(),
                ],
            );
        }
    }

    private function participantUserPool(int $count): Collection
    {
        $existing = User::where('role', UserRole::Member)->inRandomOrder()->take($count)->get();

        if ($existing->count() >= $count) {
            return $existing;
        }

        $needed = $count - $existing->count();
        User::factory()->count($needed)->create(['role' => UserRole::Member]);

        return User::where('role', UserRole::Member)->inRandomOrder()->take($count)->get();
    }

    private function generateBracket(Event $event): void
    {
        $participants = $event->participants()
            ->where('status', 'approved')
            ->orderBy('seed_number')
            ->get();

        $count = $participants->count();

        if ($count < 2) {
            return;
        }

        $rounds = (int) ceil(log($count, 2));
        $bracketSize = 2 ** $rounds;
        $matchNumber = 1;
        $matchMap = [];
        $isCompleted = $event->end_date && $event->end_date->isPast() && ! $event->canceled_at;

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);

            for ($m = 1; $m <= $matchesInRound; $m++) {
                $matchMap[$round][$m] = EventMatch::create([
                    'event_id' => $event->id,
                    'round' => $round,
                    'match_number' => $matchNumber++,
                    'status' => 'scheduled',
                    'next_match_id' => null,
                ]);
            }
        }

        foreach ($matchMap as $round => $matches) {
            if (isset($matchMap[$round + 1])) {
                foreach ($matches as $mNum => $match) {
                    $nextMatchNum = (int) ceil($mNum / 2);
                    $nextMatch = $matchMap[$round + 1][$nextMatchNum] ?? null;
                    if ($nextMatch) {
                        $match->update(['next_match_id' => $nextMatch->id]);
                    }
                }
            }
        }

        $participantSlots = $participants->values()->all();
        $byeSlots = $bracketSize - $count;

        foreach (($matchMap[1] ?? []) as $mNum => $match) {
            $p1Index = ($mNum - 1) * 2;
            $p2Index = ($mNum - 1) * 2 + 1;

            $p1 = $participantSlots[$p1Index] ?? null;
            $p2 = $participantSlots[$p2Index - $byeSlots] ?? null;

            $match->update([
                'participant1_id' => $p1?->id,
                'participant2_id' => $p2?->id,
            ]);

            if ($p1 && ! $p2) {
                $match->update(['winner_id' => $p1->id, 'score' => 'BYE', 'status' => 'completed']);
            }
        }

        if ($isCompleted) {
            $this->simulateBracketResults($matchMap, $rounds);
        }
    }

    private function simulateBracketResults(array $matchMap, int $rounds): void
    {
        for ($round = 1; $round <= $rounds; $round++) {
            foreach (($matchMap[$round] ?? []) as $mNum => $match) {
                $match->refresh();

                if ($match->status === 'completed') {
                    $winner = $match->winner_id ? EventParticipant::find($match->winner_id) : null;
                } elseif ($match->participant1_id && $match->participant2_id) {
                    $winner = rand(0, 1) ? EventParticipant::find($match->participant1_id) : EventParticipant::find($match->participant2_id);
                    $scores = ['6-4', '6-3', '7-5', '6-1', '7-6', '2-1', '3-1', '21-18'];

                    $match->update([
                        'winner_id' => $winner?->id,
                        'score' => $scores[array_rand($scores)],
                        'status' => 'completed',
                    ]);
                } else {
                    continue;
                }

                if ($winner && $match->next_match_id) {
                    $nextMatch = EventMatch::find($match->next_match_id);
                    if ($nextMatch) {
                        if (! $nextMatch->participant1_id) {
                            $nextMatch->update(['participant1_id' => $winner->id]);
                        } else {
                            $nextMatch->update(['participant2_id' => $winner->id]);
                        }
                    }
                }
            }
        }
    }

    private function participantStatus(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 60 => 'approved',
            $roll <= 75 => 'pending',
            $roll <= 88 => 'waitlisted',
            default => 'withdrawn',
        };
    }
}
