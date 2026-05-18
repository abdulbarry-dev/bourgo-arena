@component('mail::message')
# {{ match($notificationType, 'enrolled' => __('Subscription Activated'), 'suspended' => __('Subscription Suspended'), 'resumed' => __('Subscription Resumed'), 'transferred-from' => __('Subscription Transferred'), 'transferred-to' => __('Subscription Transferred'), 'expiry-reminder' => __('Subscription Expiry Reminder'), default => __('Subscription Update')) }}

{{ __('Hello') }},

@if($notificationType === 'enrolled')
{{ __('Your subscription is now active.') }}

**{{ __('Plan') }}:** {{ $subscription->plan?->name ?? __('N/A') }}<br>
**{{ __('Ends on') }}:** {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
@elseif($notificationType === 'suspended')
{{ __('Your subscription has been suspended.') }}

**{{ __('Plan') }}:** {{ $subscription->plan?->name ?? __('N/A') }}
@elseif($notificationType === 'resumed')
{{ __('Your subscription has been resumed.') }}

**{{ __('Plan') }}:** {{ $subscription->plan?->name ?? __('N/A') }}<br>
**{{ __('New End Date') }}:** {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
@elseif($notificationType === 'transferred-from')
{{ __('Your subscription has been transferred to another member by administration.') }}
@elseif($notificationType === 'transferred-to')
{{ __('A subscription has been transferred to your account.') }}

**{{ __('Plan') }}:** {{ $subscription->plan?->name ?? __('N/A') }}<br>
**{{ __('Ends on') }}:** {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
@elseif($notificationType === 'expiry-reminder')
{{ __('Reminder: your subscription is expiring soon.') }}

**{{ __('Plan') }}:** {{ $subscription->plan?->name ?? __('N/A') }}<br>
**{{ __('Expires on') }}:** {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
@else
{{ __('Your subscription has been updated.') }}

**{{ __('Status') }}:** {{ $subscription->status }}
@endif

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
