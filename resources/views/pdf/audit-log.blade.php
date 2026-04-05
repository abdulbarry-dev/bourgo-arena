<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Audit Log</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Audit Log</h2>
    <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Member Name</th>
                <th>Card UID</th>
                <th>Result</th>
                <th>Terminal</th>
                <th>Denial Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
            <tr>
                <td>{{ $event->checked_in_at }}</td>
                <td>{{ $event->member ? $event->member->name : 'Unknown' }}</td>
                <td>{{ $event->card_uid }}</td>
                <td>{{ $event->result }}</td>
                <td>{{ $event->terminal ? $event->terminal->name : 'Unknown' }}</td>
                <td>{{ $event->denial_reason }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
