<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_revenue' => 'decimal:2',
            'active_subscriptions' => 'integer',
            'expired_subscriptions' => 'integer',
            'churn_rate' => 'decimal:2',
            'revenue_by_method' => 'array',
            'plan_metrics' => 'array',
            'member_metrics' => 'array',
            'event_metrics' => 'array',
            'activity_metrics' => 'array',
        ];
    }
}
