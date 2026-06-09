<!DOCTYPE html>
<html>
<head>
    <title>{{ __('Analytics Report') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a2e; }
        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #3b82f6; }
        .header h2 { color: #1e293b; margin: 10px 0 5px; font-size: 22px; }
        .header p { color: #64748b; font-size: 13px; margin: 0; }
        .summary { display: flex; justify-content: space-between; margin: 20px 0; gap: 10px; }
        .summary .card { flex: 1; padding: 12px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center; }
        .summary .card .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary .card .value { font-size: 20px; font-weight: 700; color: #1e293b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: 600; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { color: #334155; }
        .text-right { text-align: right; }
        tfoot th { background-color: #e2e8f0; color: #1e293b; }
        h3 { color: #334155; margin: 25px 0 10px; font-size: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="data:image/webp;base64,{{ base64_encode(file_get_contents(public_path('assets/icons/brandmark-vert.webp'))) }}" alt="{{ __('Bourgo Arena Logo') }}" style="height: 40px; margin-bottom: 10px;">
        <h2>{{ __('Analytics Report') }}</h2>
        <p>{{ __('Report Interval:') }} {{ $startDate }} {{ __('to') }} {{ $endDate }}</p>
    </div>

    @php
        $totalRevenue = $snapshots->sum('total_revenue');
        $latest = $snapshots->last();
    @endphp

    <div class="summary">
        <div class="card">
            <div class="label">{{ __('Total Revenue') }}</div>
            <div class="value">${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="card">
            <div class="label">{{ __('Active Subs') }}</div>
            <div class="value">{{ $latest?->active_subscriptions ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">{{ __('Avg Churn') }}</div>
            <div class="value">{{ $snapshots->avg('churn_rate') ? number_format($snapshots->avg('churn_rate'), 1) : 0 }}%</div>
        </div>
        <div class="card">
            <div class="label">{{ __('Report Days') }}</div>
            <div class="value">{{ $snapshots->count() }}</div>
        </div>
    </div>

    <h3>{{ __('Revenue & Subscription Summary') }}</h3>
    <table>
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th class="text-right">{{ __('Total Revenue') }}</th>
                <th class="text-right">{{ __('Active Subs') }}</th>
                <th class="text-right">{{ __('Expired Subs') }}</th>
                <th class="text-right">{{ __('Churn Rate') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($snapshots as $snapshot)
                <tr>
                    <td>{{ $snapshot->date->toDateString() }}</td>
                    <td class="text-right">${{ number_format($snapshot->total_revenue, 2) }}</td>
                    <td class="text-right">{{ $snapshot->active_subscriptions }}</td>
                    <td class="text-right">{{ $snapshot->expired_subscriptions }}</td>
                    <td class="text-right">{{ $snapshot->churn_rate }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#94a3b8;">{{ __('No snapshot data for this period.') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th>{{ __('Total') }}</th>
                <th class="text-right">${{ number_format($totalRevenue, 2) }}</th>
                <th class="text-right">{{ $latest?->active_subscriptions ?? 0 }}</th>
                <th class="text-right">{{ $latest?->expired_subscriptions ?? 0 }}</th>
                <th class="text-right">{{ $latest?->churn_rate ?? 0 }}%</th>
            </tr>
        </tfoot>
    </table>

    @if ($latest && ($latest->member_metrics || $latest->event_metrics || $latest->activity_metrics))
        <h3>{{ __('Additional Metrics') }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Metric') }}</th>
                    <th class="text-right">{{ __('Value') }}</th>
                </tr>
            </thead>
            <tbody>
                @if ($metrics = $latest->member_metrics)
                    <tr><td>{{ __('Total Members') }}</td><td class="text-right">{{ $metrics['total'] ?? 0 }}</td></tr>
                    <tr><td>{{ __('Active Members') }}</td><td class="text-right">{{ $metrics['active'] ?? 0 }}</td></tr>
                    <tr><td>{{ __('New Today') }}</td><td class="text-right">{{ $metrics['new_today'] ?? 0 }}</td></tr>
                    <tr><td>{{ __('Family Accounts') }}</td><td class="text-right">{{ $metrics['family_accounts'] ?? 0 }}</td></tr>
                @endif
                @if ($metrics = $latest->event_metrics)
                    <tr><td>{{ __('Upcoming Events') }}</td><td class="text-right">{{ $metrics['upcoming'] ?? 0 }}</td></tr>
                    <tr><td>{{ __('Total Participants') }}</td><td class="text-right">{{ $metrics['total_participants'] ?? 0 }}</td></tr>
                @endif
                @if ($metrics = $latest->activity_metrics)
                    <tr><td>{{ __('Active Activities') }}</td><td class="text-right">{{ $metrics['active_activities'] ?? 0 }}</td></tr>
                    <tr><td>{{ __('Reservations Today') }}</td><td class="text-right">{{ $metrics['reservations_today'] ?? 0 }}</td></tr>
                @endif
            </tbody>
        </table>
    @endif
</body>
</html>
