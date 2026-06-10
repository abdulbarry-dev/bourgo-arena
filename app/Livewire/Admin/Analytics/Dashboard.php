<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
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

    public function loadData(): void
    {
        $service = app(AnalyticsService::class);

        $this->kpiData = $service->getKpiData();
        $this->revenueTrend = $service->getRevenueTrend();
        $this->subscriptionDistribution = $service->getSubscriptionDistribution();
        $this->memberGrowth = $service->getMemberGrowth();
        $this->revenueByMethod = $service->getRevenueByMethod();
        $this->planDistribution = $service->getPlanDistribution();
        $this->recentMembers = $service->getRecentMembers(5);
        $this->upcomingEvents = $service->getUpcomingEvents(5);
        $this->expiringSubs = $service->getExpiringSubscriptions(7);
        $this->reservationMetrics = $service->getReservationMetrics();
    }

    public function render()
    {
        return view('livewire.admin.analytics.dashboard');
    }
}
