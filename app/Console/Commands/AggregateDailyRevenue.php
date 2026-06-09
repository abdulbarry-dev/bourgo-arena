<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\Event;
use App\Models\Member;
use App\Models\Plan;
use App\Models\RevenueSnapshot;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('analytics:aggregate-revenue {--date= : The date to aggregate (Y-m-d), defaults to yesterday}')]
#[Description('Aggregates daily revenue, subscription, member, event, and activity metrics into revenue_snapshots')]
class AggregateDailyRevenue extends Command
{
    public function handle()
    {
        $dateStr = $this->option('date') ?: now()->subDay()->toDateString();
        $date = Carbon::parse($dateStr);

        $this->info("Aggregating revenue for {$dateStr}...");

        $totalRevenue = Subscription::whereDate('created_at', $date)->sum('amount_paid');
        $activeSubs = Subscription::where('status', 'active')->count();
        $expiredSubs = Subscription::where('status', 'expired')->count();

        $churnRate = ($activeSubs + $expiredSubs > 0)
            ? round(($expiredSubs / ($activeSubs + $expiredSubs)) * 100, 2)
            : 0;

        $revenueByMethod = Subscription::whereDate('created_at', $date)
            ->select('payment_method', DB::raw('SUM(amount_paid) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        $planMetrics = Subscription::where('status', 'active')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $planName = Plan::find($item->plan_id)?->name ?? 'Unknown';

                return [$planName => $item->count];
            })
            ->toArray();

        $memberMetrics = [
            'total' => Member::count(),
            'active' => Member::where('status', 'active')->count(),
            'new_today' => Member::whereDate('created_at', $date)->count(),
            'pending_verification' => Member::where('status', 'pending_verification')->count(),
            'pending_onboarding' => Member::where('state', 'pending_onboarding')->count(),
            'family_accounts' => Member::where('is_family_account', true)->count(),
        ];

        $eventMetrics = [
            'upcoming' => Event::whereNull('canceled_at')
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '>=', now());
                })
                ->count(),
            'in_progress' => Event::whereNull('canceled_at')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
            'completed' => Event::whereNull('canceled_at')
                ->where('end_date', '<', now())
                ->count(),
            'canceled' => Event::whereNotNull('canceled_at')->count(),
            'total_participants' => DB::table('event_participants')
                ->whereDate('created_at', '<=', $date)
                ->count(),
        ];

        $activityMetrics = [
            'active_activities' => Activity::where('is_active', true)->count(),
            'reservations_today' => ApiReservation::whereDate('created_at', $date)->count(),
            'revenue_from_reservations' => ApiReservation::whereDate('created_at', $date)
                ->sum('price'),
        ];

        RevenueSnapshot::updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'total_revenue' => $totalRevenue,
                'active_subscriptions' => $activeSubs,
                'expired_subscriptions' => $expiredSubs,
                'churn_rate' => $churnRate,
                'revenue_by_method' => $revenueByMethod,
                'plan_metrics' => $planMetrics,
                'member_metrics' => $memberMetrics,
                'event_metrics' => $eventMetrics,
                'activity_metrics' => $activityMetrics,
            ]
        );

        $this->info("Successfully aggregated revenue for {$dateStr}.");
    }
}
