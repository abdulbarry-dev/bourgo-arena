<?php

namespace App\Providers;

use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Policies\MemberPolicy;
use App\Policies\PlanPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\SubscriptionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Member::class => MemberPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        Plan::class => PlanPolicy::class,
        ApiReservation::class => ReservationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('viewApiDocs', function (?Member $member) {
            return true;
        });
    }
}
