<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Url(as: 'period')]
    public string $period = '30_days';

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
        $this->loadData();
    }

    public function updatedPeriod(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loading = true;

        $service = app(AnalyticsService::class);
        $days = $this->intervalDays();

        $this->kpiData = $service->getKpiData();
        $this->revenueTrend = $service->getRevenueTrend($days);
        $this->subscriptionDistribution = $service->getSubscriptionDistribution();
        $this->memberGrowth = $service->getMemberGrowth($days);
        $this->revenueByMethod = $service->getRevenueByMethod($days);
        $this->planDistribution = $service->getPlanDistribution();
        $this->recentMembers = $service->getRecentMembers(5);
        $this->upcomingEvents = $service->getUpcomingEvents(5);
        $this->expiringSubs = $service->getExpiringSubscriptions(7);
        $this->reservationMetrics = $service->getReservationMetrics($days);

        $this->loading = false;
    }

    public function intervalDays(): int
    {
        return match ($this->period) {
            '90_days' => 90,
            '12_months' => 365,
            default => 30,
        };
    }

    public function render()
    {
        return view('livewire.admin.analytics.dashboard');
    }
}
