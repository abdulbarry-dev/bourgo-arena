<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Subscriptions') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="data:image/webp;base64,{{ base64_encode(file_get_contents(public_path('assets/icons/brandmark-vert.webp'))) }}" alt="{{ __('Bourgo Arena Logo') }}" style="height: 48px; margin-bottom: 15px;">
        <h2 style="margin-top: 0;">{{ __('Subscriptions') }}</h2>
    </div>
    <p>{{ __('Generated on:') }} {{ now()->format('Y-m-d H:i:s') }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('Member') }}</th>
                <th>{{ __('Plan') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Start Date') }}</th>
                <th>{{ __('End Date') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscriptions as $sub)
            <tr>
                <td>{{ $sub->member ? $sub->member->name : __('Unknown') }}</td>
                <td>{{ $sub->plan ? __($sub->plan->name) : __('Unknown') }}</td>
                <td>{{ ucfirst(__($sub->status)) }}</td>
                <td>{{ $sub->starts_at ? $sub->starts_at->format('Y-m-d') : '' }}</td>
                <td>{{ $sub->ends_at ? $sub->ends_at->format('Y-m-d') : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
