<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    #[Url(as: 'range')]
    public string $preset = '30_days';

    public bool $loading = true;

    public array $kpiData = [];
    public array $revenueTrend = [];
    public array $subscriptionDistribution = [];
    public array $memberGrowth = [];
    public array $revenueByMethod = [];
    public array $planDistribution = [];
    public array $recentMembers = [];
    public array $upcomingEvents = [];
    public array $expiringSubs = [];
    public array $reservationMetrics = [];

    public function mount(): void
    {
        $this->syncDatesFromPreset();
        $this->loadData();
    }

    public function updatedPreset(): void
    {
        $this->syncDatesFromPreset();
        $this->loadData();
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
    }

    public function updatedTo(): void
    {
        $this->preset = 'custom';
    }

    protected function syncDatesFromPreset(): void
    {
        if ($this->preset === 'custom') {
            if (empty($this->from)) {
                $this->from = now()->subDays(30)->toDateString();
            }
            if (empty($this->to)) {
                $this->to = now()->toDateString();
            }

            return;
        }

        $days = match ($this->preset) {
            '90_days' => 90,
            '12_months' => 365,
            default => 30,
        };

        $this->from = now()->subDays($days)->toDateString();
        $this->to = now()->toDateString();
    }

    public function loadData(): void
    {
        $this->loading = true;

        $service = app(AnalyticsService::class);

        $this->kpiData = $service->getKpiData();
        $this->revenueTrend = $service->getRevenueTrend(from: $this->from, to: $this->to);
        $this->subscriptionDistribution = $service->getSubscriptionDistribution();
        $this->memberGrowth = $service->getMemberGrowth(from: $this->from, to: $this->to);
        $this->revenueByMethod = $service->getRevenueByMethod(from: $this->from, to: $this->to);
        $this->planDistribution = $service->getPlanDistribution();
        $this->recentMembers = $service->getRecentMembers(5);
        $this->upcomingEvents = $service->getUpcomingEvents(5);
        $this->expiringSubs = $service->getExpiringSubscriptions(7);
        $this->reservationMetrics = $service->getReservationMetrics(from: $this->from, to: $this->to);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.admin.analytics.dashboard');
    }
}
