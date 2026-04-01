<?php

use App\Livewire\Admin\Members\MemberTable;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('member search with one thousand records stays under five hundred milliseconds', function () {
    $this->actingAs(User::factory()->manager()->create());
    Member::factory()->count(980)->create();
    Member::factory()->count(20)->create(['name' => 'Benchmark Search Member']);

    $component = app(MemberTable::class);
    $component->search = 'Benchmark Search';
    $component->perPage = 50;

    $startedAt = hrtime(true);
    $paginator = $component->members();
    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

    expect($paginator->total())->toBeGreaterThan(0);
    expect($elapsedMilliseconds)->toBeLessThan(500.0);
});

test('csv export for five hundred members stays under two seconds', function () {
    $this->actingAs(User::factory()->manager()->create());
    Member::factory()->count(500)->create();

    $component = app(MemberTable::class);

    $startedAt = hrtime(true);
    $response = $component->exportCsv();

    $callback = $response->getCallback();

    ob_start();
    $callback();
    $csv = (string) ob_get_clean();

    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

    expect($response->headers->get('content-disposition'))->toContain('members.csv');
    expect($csv)->toContain('Name,Email,Phone,Status,Plan,"NFC Status"');
    expect($elapsedMilliseconds)->toBeLessThan(2000.0);
});

test('member email lookup explain plan uses an index', function () {
    Member::factory()->count(1000)->create();
    $targetMember = Member::factory()->create(['email' => 'indexed.member@example.com']);

    $driver = DB::connection()->getDriverName();

    if ($driver === 'sqlite') {
        $email = str_replace("'", "''", $targetMember->email);
        $explainRows = DB::select("EXPLAIN QUERY PLAN SELECT id, email FROM members WHERE email = '{$email}' AND deleted_at IS NULL LIMIT 1");

        $plan = collect($explainRows)
            ->map(fn (object $row): string => (string) ($row->detail ?? ''))
            ->implode("\n");
    } else {
        $explainRows = DB::select(
            'EXPLAIN ANALYZE SELECT id, email FROM members WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [$targetMember->email],
        );

        $plan = collect($explainRows)
            ->map(fn (object $row): string => $row->{'QUERY PLAN'})
            ->implode("\n");
    }

    expect(strtolower($plan))->toContain('index');
});
