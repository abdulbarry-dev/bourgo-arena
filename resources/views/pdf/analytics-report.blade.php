<!DOCTYPE html>
<html>
<head>
    <title>{{ __('Analytics Report') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <img src="data:image/webp;base64,{{ base64_encode(file_get_contents(public_path('assets/images/brandmark-noir.webp'))) }}" alt="{{ __('Bourgo Arena Logo') }}" style="height: 48px; margin-bottom: 15px;">
        <h2>{{ __('Revenue & Subscription Analytics') }}</h2>
        <p>{{ __('Report Interval:') }} {{ $startDate }} {{ __('to') }} {{ $endDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th class="text-right">{{ __('Total Revenue') }}</th>
                <th class="text-right">{{ __('Active Subs') }}</th>
                <th class="text-right">{{ __('Expired Subs') }}</th>
                <th class="text-right">{{ __('Churn Rate (%)') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($snapshots as $snapshot)
                <tr>
                    <td>{{ $snapshot->date->toDateString() }}</td>
                    <td class="text-right">{{ number_format($snapshot->total_revenue, 2) }}</td>
                    <td class="text-right">{{ $snapshot->active_subscriptions }}</td>
                    <td class="text-right">{{ $snapshot->expired_subscriptions }}</td>
                    <td class="text-right">{{ $snapshot->churn_rate }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>{{ __('Total') }}</th>
                <th class="text-right">{{ number_format($snapshots->sum('total_revenue'), 2) }}</th>
                <th class="text-right">-</th>
                <th class="text-right">-</th>
                <th class="text-right">-</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
