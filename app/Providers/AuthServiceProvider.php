<?php

namespace App\Providers;

use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Policies\MemberPolicy;
use App\Policies\PlanPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\SubscriptionPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Member::class => MemberPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        Plan::class => PlanPolicy::class,
        ApiReservation::class => ReservationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('viewApiDocs', function (?Member $member) {
            return true;
        });

        Gate::define('access-dashboard-module', function (User $user, string $module) {
            return match ($module) {
                'dashboard', 'members', 'subscriptions', 'schedule', 'reservations' => $user->isStaff(),
                'courses', 'events', 'plans', 'managers' => $user->isAdmin(),
                'activities' => $user->isStaff(),
                default => false,
            };
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            return new QueuedResetPassword($token);
        });
    }
}
