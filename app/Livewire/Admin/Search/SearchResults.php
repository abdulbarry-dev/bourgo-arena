<?php

namespace App\Livewire\Admin\Search;

use App\Services\GlobalSearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchResults extends Component
{
    use WithPagination;

    /** @var string The full-text search query, synced with the URL. */
    #[Url(as: 'query')]
    public string $query = '';

    /** @var string The currently active entity tab. */
    #[Url(as: 'type')]
    public string $activeTab = 'members';

    /** @var array<string, int> Cached counts per type. */
    public array $typeCounts = [];

    /** Available tabs in display order. */
    public const TABS = [
        'members' => ['label' => 'Members', 'icon' => 'user-group'],
        'events' => ['label' => 'Events', 'icon' => 'trophy'],
        'courses' => ['label' => 'Courses', 'icon' => 'book-open'],
        'subscriptions' => ['label' => 'Subscriptions', 'icon' => 'credit-card'],
        'services' => ['label' => 'Services', 'icon' => 'puzzle-piece'],
        'plans' => ['label' => 'Plans', 'icon' => 'clipboard-document-list'],
        'activities' => ['label' => 'Activities', 'icon' => 'bolt'],
    ];

    public function mount(): void
    {
        $this->refreshCounts();
    }

    public function updatedQuery(): void
    {
        $this->resetPage();
        $this->refreshCounts();
    }

    public function switchTab(string $tab): void
    {
        if (! array_key_exists($tab, self::TABS)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    private function refreshCounts(): void
    {
        if (blank($this->query)) {
            $this->typeCounts = array_fill_keys(array_keys(self::TABS), 0);

            return;
        }

        /** @var GlobalSearchService $service */
        $service = app(GlobalSearchService::class);
        $this->typeCounts = $service->countByType($this->query);
    }

    #[Computed]
    public function paginatedResults(): LengthAwarePaginator
    {
        if (blank($this->query)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        /** @var GlobalSearchService $service */
        $service = app(GlobalSearchService::class);

        return $service->searchType($this->activeTab, $this->query, perPage: 10);
    }

    #[Computed]
    public function totalResults(): int
    {
        return array_sum($this->typeCounts);
    }

    public function render(): View
    {
        return view('livewire.admin.search.search-results')
            ->layout('layouts.app', ['title' => __('Search')]);
    }
}
