<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Reconciliations') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f4f4f4; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="data:image/webp;base64,{{ base64_encode(file_get_contents(public_path('assets/icons/brandmark-vert.webp'))) }}" alt="{{ __('Bourgo Arena Logo') }}" style="height: 48px; margin-bottom: 15px;">
        <h2 style="margin-top: 0;">{{ __('Reconciliations') }}</h2>
    </div>

    <p>{{ __('Generated on:') }} {{ $generatedAt->format('Y-m-d H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('When') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Payment') }}</th>
                <th>{{ __('Admin') }}</th>
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Metadata') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ ucfirst($item->type) }}</td>
                    <td>{{ $item->payment?->payment_reference ?? ('#'.$item->payment_id) }}</td>
                    <td>{{ $item->admin?->name ?? __('System') }}</td>
                    <td>{{ $item->amount !== null ? number_format((float) $item->amount, 3, '.', '') : '' }}</td>
                    <td>{{ json_encode($item->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>