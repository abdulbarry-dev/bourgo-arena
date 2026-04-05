<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Subscriptions</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Subscriptions</h2>
    <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    <table>
        <thead>
            <tr>
                <th>Member</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscriptions as $sub)
            <tr>
                <td>{{ $sub->member ? $sub->member->name : 'Unknown' }}</td>
                <td>{{ $sub->plan ? __($sub->plan->name) : 'Unknown' }}</td>
                <td>{{ ucfirst($sub->status) }}</td>
                <td>{{ $sub->starts_at ? $sub->starts_at->format('Y-m-d') : '' }}</td>
                <td>{{ $sub->ends_at ? $sub->ends_at->format('Y-m-d') : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
