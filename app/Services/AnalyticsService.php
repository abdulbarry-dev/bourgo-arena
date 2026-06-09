<?php

namespace App\Services;

use App\Models\ApiReservation;
use App\Models\Event;
use App\Models\Member;
use App\Models\OccupancyHourlyAggregate;
use App\Models\Payment;
use App\Models\RevenueSnapshot;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getKpiData(): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $revenueMtd = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $revenueLastMonth = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $activeSubs = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->count();

        $subsLastMonth = Subscription::where('status', 'active')
            ->where('created_at', '<', $startOfMonth)
            ->count();

        $totalMembers = Member::count();
        $membersLastMonth = Member::where('created_at', '<', $startOfMonth)->count();

        $todayOccupancy = OccupancyHourlyAggregate::whereDate('date', today())
            ->avg('avg_occupancy');

        $yesterdayOccupancy = OccupancyHourlyAggregate::whereDate('date', today()->subDay())
            ->avg('avg_occupancy');

        return [
            'revenue_mtd' => round((float) $revenueMtd, 2),
            'revenue_change' => $revenueLastMonth > 0
                ? round((($revenueMtd - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : 0,
            'active_subs' => $activeSubs,
            'subs_change' => $subsLastMonth > 0
                ? round((($activeSubs - $subsLastMonth) / $subsLastMonth) * 100, 1)
                : 0,
            'total_members' => $totalMembers,
            'members_change' => $membersLastMonth > 0
                ? round((($totalMembers - $membersLastMonth) / $membersLastMonth) * 100, 1)
                : 0,
            'today_occupancy' => round((float) ($todayOccupancy ?: 0)),
            'occupancy_change' => $yesterdayOccupancy > 0
                ? round(((($todayOccupancy ?: 0) - $yesterdayOccupancy) / $yesterdayOccupancy) * 100, 1)
                : 0,
        ];
    }

    public function getRevenueTrend(int $days = 30): array
    {
        $snapshots = RevenueSnapshot::where('date', '>=', now()->subDays($days + 1))
            ->orderBy('date')
            ->get();

        $labels = $snapshots->map(fn ($s) => $s->date->format('M d'))->toArray();
        $values = $snapshots->map(fn ($s) => (float) $s->total_revenue)->toArray();

        $change = 0;
        if (count($values) >= 2) {
            $first = $values[0];
            $last = $values[count($values) - 1];
            $change = $first > 0 ? round((($last - $first) / $first) * 100, 1) : 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'change' => $change,
        ];
    }

    public function getSubscriptionDistribution(): array
    {
        $active = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->count();

        $expired = Subscription::where('status', 'expired')->count();
        $suspended = Subscription::where('status', 'suspended')->count();

        return [
            'labels' => ['Active', 'Expired', 'Suspended'],
            'values' => [$active, $expired, $suspended],
        ];
    }

    public function getMemberGrowth(int $days = 30): array
    {
        $records = Member::where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dates = collect();
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates->push([
                'date' => now()->subDays($i)->format('M d'),
                'count' => (int) ($records->get($date)?->count ?? 0),
            ]);
        }

        return [
            'labels' => $dates->pluck('date')->toArray(),
            'values' => $dates->pluck('count')->toArray(),
        ];
    }

    public function getRevenueByMethod(int $days = 30): array
    {
        $snapshots = RevenueSnapshot::where('date', '>=', now()->subDays($days))
            ->get();

        $aggregated = [];
        foreach ($snapshots as $snapshot) {
            $methods = $snapshot->revenue_by_method ?? [];
            foreach ($methods as $method => $total) {
                $aggregated[$method] = ($aggregated[$method] ?? 0) + (float) $total;
            }
        }

        if (empty($aggregated)) {
            $payments = Payment::where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->select('gateway', DB::raw('SUM(amount) as total'))
                ->groupBy('gateway')
                ->pluck('total', 'gateway')
                ->toArray();

            $aggregated = $payments;
        }

        return [
            'labels' => array_keys($aggregated),
            'values' => array_values($aggregated),
        ];
    }

    public function getPlanDistribution(): array
    {
        $planMetrics = Subscription::where('status', 'active')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->with('plan:id,name')
            ->get();

        $labels = $planMetrics->map(fn ($s) => $s->plan?->name ?? 'Unknown')->toArray();
        $values = $planMetrics->pluck('count')->toArray();

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    public function getRecentMembers(int $limit = 5): array
    {
        return Member::with('validSubscriptions.plan')
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'avatar_url' => $member->avatar_url,
                'initials' => $member->initials(),
                'status' => $member->status,
                'plan' => $member->validSubscriptions->first()?->plan?->name,
                'created_at' => $member->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function getUpcomingEvents(int $limit = 5): array
    {
        return Event::withCount('participants')
            ->whereNull('canceled_at')
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '>=', now());
            })
            ->orderBy('start_date')
            ->take($limit)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'name' => $event->name,
                'start_date' => $event->start_date,
                'participants_count' => $event->participants_count,
                'max_participants' => $event->max_participants,
                'days_until' => $event->start_date
                    ? (int) now()->startOfDay()->diffInDays($event->start_date, false)
                    : null,
            ])
            ->toArray();
    }

    public function getExpiringSubscriptions(int $days = 7): array
    {
        return Subscription::with('member', 'plan')
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->where('ends_at', '<=', now()->addDays($days))
            ->orderBy('ends_at')
            ->take(10)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'member_name' => $sub->member?->name ?? 'Unknown',
                'member_id' => $sub->member_id,
                'plan_name' => $sub->plan?->name ?? 'Unknown',
                'ends_at' => $sub->ends_at?->format('M d, Y'),
                'days_remaining' => $sub->daysRemaining(),
            ])
            ->toArray();
    }

    public function getMemberStatusDistribution(): array
    {
        $statuses = Member::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'labels' => array_keys($statuses),
            'values' => array_values($statuses),
        ];
    }

    public function getReservationMetrics(int $days = 30): array
    {
        $total = ApiReservation::where('created_at', '>=', now()->subDays($days))->count();
        $canceled = ApiReservation::whereNotNull('cancelled_at')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
        $revenue = ApiReservation::where('created_at', '>=', now()->subDays($days))
            ->sum('price');

        return [
            'total_reservations' => $total,
            'canceled_reservations' => $canceled,
            'cancellation_rate' => $total > 0 ? round(($canceled / $total) * 100, 1) : 0,
            'reservation_revenue' => round((float) $revenue, 2),
        ];
    }

    public function getOccupancyTrend(int $days = 7): array
    {
        $records = OccupancyHourlyAggregate::where('date', '>=', now()->subDays($days))
            ->select(
                'date',
                DB::raw('AVG(avg_occupancy) as avg_occupancy'),
                DB::raw('SUM(entries_count) as total_entries'),
                DB::raw('SUM(exits_count) as total_exits')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $records->map(fn ($r) => $r->date->format('M d'))->toArray(),
            'occupancy' => $records->map(fn ($r) => (int) round($r->avg_occupancy))->toArray(),
            'entries' => $records->map(fn ($r) => (int) ($r->total_entries ?? 0))->toArray(),
            'exits' => $records->map(fn ($r) => (int) ($r->total_exits ?? 0))->toArray(),
        ];
    }
}
