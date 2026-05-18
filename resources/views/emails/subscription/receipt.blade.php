@component('mail::message')
# {{ __(':app Subscription Receipt', ['app' => config('app.name')]) }}

{{ __('Hello') }},

{{ __('Your subscription enrollment has been recorded.') }}

**{{ __('Plan') }}:** {{ $subscription->plan?->name ?? __('N/A') }}<br>
**{{ __('Amount Paid') }}:** {{ $subscription->amount_paid ? number_format((float) $subscription->amount_paid, 3, '.', '') . ' TND' : __('N/A') }}<br>
**{{ __('Status') }}:** {{ ucfirst($subscription->status) }}

@if($subscription->ends_at)
**{{ __('Subscription Ends') }}:** {{ $subscription->ends_at->format('Y-m-d') }}
@endif

{{ __('Keep this email for your records.') }}

{{ __('If you have any questions about your subscription, please contact our support team.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
