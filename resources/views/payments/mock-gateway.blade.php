<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Bourgo Arena</h1>
            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">SANDBOX</span>
        </div>

        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-1">Payment for</p>
            <p class="font-medium text-gray-800">{{ $description }}</p>
        </div>

        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-1">Amount to pay</p>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($amount, 3) }} TND</p>
        </div>

        <div class="space-y-4">
            <form action="{{ $success_url }}" method="GET">
                @foreach($query_params as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="status" value="paid">
                <input type="hidden" name="payment_id" value="{{ $payment_id }}">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition duration-200">
                    Simulate Success
                </button>
            </form>

            <form action="{{ $failure_url }}" method="GET">
                @foreach($query_params as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="status" value="failed">
                <input type="hidden" name="payment_id" value="{{ $payment_id }}">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition duration-200">
                    Simulate Failure
                </button>
            </form>
        </div>

        <p class="mt-8 text-center text-xs text-gray-500">
            This is a mock payment gateway for development purposes. No real money will be charged.
        </p>
    </div>
</body>
</html>
