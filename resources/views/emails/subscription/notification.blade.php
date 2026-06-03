<x-mail.layout>

<!-- Logo -->
<!-- logo removed per request -->

<!-- Heading -->
<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ match($notificationType, 'enrolled' => __('Subscription'), 'suspended' => __('Subscription'), 'resumed' => __('Subscription'), 'expiry-reminder' => __('Subscription'), default => __('Subscription')) }}<br>
  <span style="color: #c8f000;">
    {{ match($notificationType, 'enrolled' => __('Activated'), 'suspended' => __('Suspended'), 'resumed' => __('Resumed'), 'expiry-reminder' => __('Reminder'), default => __('Updated')) }}
  </span>
</div>

<!-- Body text -->
<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello,') }}<br>
  @if($notificationType === 'enrolled')
    {{ __('Your subscription is now active.') }}
  @elseif($notificationType === 'suspended')
    {{ __('Your subscription has been suspended.') }}
  @elseif($notificationType === 'resumed')
    {{ __('Your subscription has been resumed.') }}
  @elseif($notificationType === 'expiry-reminder')
    {{ __('Reminder: your subscription is expiring soon.') }}
  @else
    {{ __('Your subscription has been updated.') }}
  @endif
</p>

<!-- Info block -->
<div style="border-left: 3px solid #c8f000; background-color: #222222; padding: 14px 16px; border-radius: 0 4px 4px 0; margin-bottom: 32px;">
  <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff; margin-bottom: 5px;">{{ __('Subscription Details') }}</div>
  <div style="font-size: 13px; color: #999999; line-height: 1.5;">
    <strong style="color: #c8f000;">{{ __('Plan') }}:</strong> {{ $subscription->plan?->name ?? __('N/A') }}<br>
    @if($notificationType === 'enrolled')
      <strong style="color: #c8f000;">{{ __('Ends on') }}:</strong> {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
    @elseif($notificationType === 'resumed')
      <strong style="color: #c8f000;">{{ __('New End Date') }}:</strong> {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
    @elseif($notificationType === 'expiry-reminder')
      <strong style="color: #c8f000;">{{ __('Expires on') }}:</strong> {{ $subscription->ends_at?->format('Y-m-d') ?? __('N/A') }}
    @elseif($notificationType !== 'suspended')
      <strong style="color: #c8f000;">{{ __('Status') }}:</strong> {{ ucfirst($subscription->status) }}
    @endif
  </div>
</div>

</x-mail.layout>
