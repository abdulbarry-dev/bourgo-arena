<?php

namespace App\Providers;

use App\Models\HikvisionTerminal;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\Plan;
use App\Models\Subscription;
use App\Policies\HikvisionTerminalPolicy;
use App\Policies\MemberPolicy;
use App\Policies\NfcCardPolicy;
use App\Policies\PlanPolicy;
use App\Policies\SubscriptionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        HikvisionTerminal::class => HikvisionTerminalPolicy::class,
        NfcCard::class => NfcCardPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
