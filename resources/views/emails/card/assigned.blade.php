<x-mail::layout>

<!-- Logo -->
<!-- logo removed per request -->

<!-- Heading -->
<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ __('Your NFC Card') }}<br>
  <span style="color: #c8f000;">{{ __('Assigned') }}</span>
</div>

<!-- Body text -->
<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello :name,', ['name' => $member->name]) }}<br>
  {{ __('Your :app NFC card has been successfully assigned to your account.', ['app' => config('app.name')]) }}
</p>

<!-- Info block -->
<div style="border-left: 3px solid #c8f000; background-color: #222222; padding: 14px 16px; border-radius: 0 4px 4px 0; margin-bottom: 32px;">
  <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff; margin-bottom: 5px;">{{ __('Card Details') }}</div>
  <div style="font-size: 13px; color: #999999; line-height: 1.5;">
    <strong style="color: #c8f000;">{{ __('Card UID') }}:</strong> {{ $member->nfcCard?->uid ?? __('N/A') }}<br>
    <strong style="color: #c8f000;">{{ __('Status') }}:</strong> {{ ucfirst($member->nfcCard?->status ?? __('N/A')) }}
  </div>
</div>

</x-mail::layout>
