<x-mail.layout>

<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ $type === 'gift' ? __('Loyalty') : __('Loyalty') }}<br>
  <span style="color: #c8f000;">
    {{ $type === 'gift' ? __('Gifted') : __('Adjusted') }}
  </span>
</div>

<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello,') }}<br>
  @if($type === 'gift')
    {{ __('Great news! You have received a loyalty points gift from Bourgo Arena.') }}
  @else
    {{ __('Your loyalty points balance has been adjusted by an administrator.') }}
  @endif
</p>

<div style="border-left: 3px solid #c8f000; background-color: #222222; padding: 14px 16px; border-radius: 0 4px 4px 0; margin-bottom: 32px;">
  <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff; margin-bottom: 5px;">{{ __('Adjustment Details') }}</div>
  <div style="font-size: 13px; color: #999999; line-height: 1.5;">
    <strong style="color: #c8f000;">{{ __('Points') }}:</strong> {{ $type === 'gift' ? '+' : '-' }}{{ number_format($pointsChanged) }}<br>
    <strong style="color: #c8f000;">{{ __('Reason') }}:</strong> {{ $reason }}<br>
    <strong style="color: #c8f000;">{{ __('New Balance') }}:</strong> {{ number_format($member->loyalty_points) }}
  </div>
</div>

</x-mail.layout>
