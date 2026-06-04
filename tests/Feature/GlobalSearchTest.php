<?php

use App\Livewire\Admin\Search\SearchResults;
use App\Livewire\Shared\GlobalSearch;
use App\Models\Course;
use App\Models\Event;
use App\Models\Member;
use App\Models\Service;
use App\Models\User;
use App\Services\GlobalSearchService;
use Livewire\Livewire;

// -------------------------------------------------------------------------
// GlobalSearchService Unit Tests
// -------------------------------------------------------------------------

test('global search service returns empty results for empty query', function () {
    $service = app(GlobalSearchService::class);
    $results = $service->search('');

    foreach ($results as $group) {
        expect($group)->toBeEmpty();
    }
});

test('global search service returns empty results for whitespace-only query', function () {
    $service = app(GlobalSearchService::class);
    $results = $service->search('   ');

    foreach ($results as $group) {
        expect($group)->toBeEmpty();
    }
});

test('global search service finds members by name', function () {
    Member::factory()->create(['name' => 'Youssef Alami', 'email' => 'youssef@example.com']);
    Member::factory()->create(['name' => 'Fatima Zahra', 'email' => 'fatima@example.com']);

    $service = app(GlobalSearchService::class);
    $results = $service->search('Youssef');

    expect($results['members'])->toHaveCount(1);
    expect($results['members']->first()->name)->toBe('Youssef Alami');
});

test('global search service finds members by email', function () {
    Member::factory()->create(['name' => 'Test Member', 'email' => 'findme@arena.com']);
    Member::factory()->create(['name' => 'Other Member', 'email' => 'other@arena.com']);

    $service = app(GlobalSearchService::class);
    $results = $service->search('findme');

    expect($results['members'])->toHaveCount(1);
    expect($results['members']->first()->email)->toBe('findme@arena.com');
});

test('global search service finds events by name', function () {
    $service_model = Service::factory()->create();
    Event::factory()->create(['name' => 'Spring Tournament', 'service_id' => $service_model->id]);
    Event::factory()->create(['name' => 'Winter Cup', 'service_id' => $service_model->id]);

    $service = app(GlobalSearchService::class);
    $results = $service->search('Spring');

    expect($results['events'])->toHaveCount(1);
    expect($results['events']->first()->name)->toBe('Spring Tournament');
});

test('global search service finds courses by name', function () {
    $service_model = Service::factory()->create();
    Course::factory()->create(['name' => 'Advanced Swimming', 'service_id' => $service_model->id]);
    Course::factory()->create(['name' => 'Beginner Yoga', 'service_id' => $service_model->id]);

    $service = app(GlobalSearchService::class);
    $results = $service->search('Swimming');

    expect($results['courses'])->toHaveCount(1);
    expect($results['courses']->first()->name)->toBe('Advanced Swimming');
});

test('global search service respects limit for palette mode', function () {
    Member::factory()->count(10)->create(['name' => 'SearchableUser']);

    $service = app(GlobalSearchService::class);
    $results = $service->search('SearchableUser', limit: 5);

    expect($results['members'])->toHaveCount(5);
});

test('global search service countByType returns zeros for empty query', function () {
    $service = app(GlobalSearchService::class);
    $counts = $service->countByType('');

    foreach ($counts as $count) {
        expect($count)->toBe(0);
    }
});

test('global search service countByType returns correct member count', function () {
    Member::factory()->create(['name' => 'Counted Member One']);
    Member::factory()->create(['name' => 'Counted Member Two']);
    Member::factory()->create(['name' => 'Different Person']);

    $service = app(GlobalSearchService::class);
    $counts = $service->countByType('Counted Member');

    expect($counts['members'])->toBe(2);
});

test('global search service searchType paginates correctly', function () {
    Member::factory()->count(20)->create(['name' => 'PaginatedMember']);

    $service = app(GlobalSearchService::class);
    $page = $service->searchType('members', 'PaginatedMember', perPage: 10);

    expect($page->total())->toBe(20);
    expect($page->perPage())->toBe(10);
    expect($page->items())->toHaveCount(10);
});

test('global search service does not throw on special characters', function () {
    $service = app(GlobalSearchService::class);

    expect(fn () => $service->search('%_test%'))->not->toThrow(Exception::class);
    expect(fn () => $service->search("O'Brien"))->not->toThrow(Exception::class);
});

// -------------------------------------------------------------------------
// GlobalSearch Livewire Component Tests (palette)
// -------------------------------------------------------------------------

test('global search palette renders without errors', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(GlobalSearch::class)
        ->assertOk();
});

test('global search palette starts closed', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(GlobalSearch::class)
        ->assertSet('isOpen', false);
});

test('global search palette opens on openPalette call', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(GlobalSearch::class)
        ->call('openPalette')
        ->assertSet('isOpen', true);
});

test('global search palette closes and resets query on closePalette call', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(GlobalSearch::class)
        ->call('openPalette')
        ->set('query', 'something')
        ->call('closePalette')
        ->assertSet('isOpen', false)
        ->assertSet('query', '');
});

test('global search palette returns results for matching query', function () {
    $this->actingAs(User::factory()->admin()->create());

    Member::factory()->create(['name' => 'PaletteTestMember', 'email' => 'palette@test.com']);

    Livewire::test(GlobalSearch::class)
        ->set('query', 'PaletteTestMember')
        ->assertSee('PaletteTestMember');
});

test('global search palette navigates to results page', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(GlobalSearch::class)
        ->set('query', 'test')
        ->call('navigateToResults')
        ->assertRedirect(route('admin.search', ['query' => 'test']));
});

test('global search palette does not navigate when query is blank', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(GlobalSearch::class)
        ->set('query', '')
        ->call('navigateToResults')
        ->assertNoRedirect();
});

// -------------------------------------------------------------------------
// SearchResults Full-Page Component Tests
// -------------------------------------------------------------------------

test('search results page is accessible to admins', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.search'))
        ->assertSuccessful();
});

test('search results page is accessible to managers', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->get(route('admin.search'))
        ->assertSuccessful();
});

test('search results page renders with query from url', function () {
    $this->actingAs(User::factory()->admin()->create());

    Member::factory()->create(['name' => 'UrlQueryMember', 'email' => 'url@test.com']);

    Livewire::test(SearchResults::class, ['query' => 'UrlQueryMember'])
        ->assertSee('UrlQueryMember');
});

test('search results page shows no results state for unmatched query', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SearchResults::class, ['query' => 'xyzxyzxyz_notfound_123'])
        ->assertSee('No');
});

test('search results tab switching works', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SearchResults::class)
        ->assertSet('activeTab', 'members')
        ->call('switchTab', 'events')
        ->assertSet('activeTab', 'events');
});

test('search results ignores invalid tab names', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SearchResults::class)
        ->assertSet('activeTab', 'members')
        ->call('switchTab', 'invalid_tab')
        ->assertSet('activeTab', 'members');
});

test('search results page type counts update when query changes', function () {
    $this->actingAs(User::factory()->admin()->create());

    Member::factory()->create(['name' => 'CountQueryMember']);

    $component = Livewire::test(SearchResults::class)
        ->set('query', 'CountQueryMember');

    expect($component->get('typeCounts')['members'])->toBeGreaterThanOrEqual(1);
});
