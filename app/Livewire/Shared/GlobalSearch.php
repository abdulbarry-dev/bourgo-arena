<?php

namespace App\Livewire\Shared;

use App\Services\GlobalSearchService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public bool $isOpen = false;

    public int $highlightedIndex = -1;

    public function openPalette(): void
    {
        $this->isOpen = true;
        $this->query = '';
        $this->highlightedIndex = -1;
    }

    public function closePalette(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->highlightedIndex = -1;
    }

    public function updatedQuery(): void
    {
        $this->highlightedIndex = -1;
    }

    public function navigateToResults(): void
    {
        if (blank($this->query)) {
            return;
        }

        $query = $this->query;
        $this->closePalette();
        $this->redirect(route('admin.search', ['query' => $query]), navigate: true);
    }

    #[Computed]
    public function results(): array
    {
        if (blank($this->query) || strlen($this->query) < 2) {
            return [];
        }

        /** @var GlobalSearchService $service */
        $service = app(GlobalSearchService::class);

        return $service->search($this->query, limit: 5);
    }

    #[Computed]
    public function hasResults(): bool
    {
        return collect($this->results)->flatten()->isNotEmpty();
    }

    #[Computed]
    public function totalCount(): int
    {
        return collect($this->results)->sum(fn ($group) => $group->count());
    }

    public function render(): View
    {
        return view('livewire.shared.global-search');
    }
}
