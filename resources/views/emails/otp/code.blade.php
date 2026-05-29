<x-mail.layout>

<!-- Logo -->
<!-- logo removed per request -->

<!-- Heading -->
<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ __('Your Verification') }}<br>
  <span style="color: #c8f000;">{{ __('Code') }}</span>
</div>

<!-- Body text -->
<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello') }}{{ $userName ? ' ' . $userName : '' }},<br>
  {{ __('Your verification code is:') }}
</p>

<!-- Code Block -->
<div style="text-align: center; padding: 24px 0; margin: 24px 0;">
  <div style="font-size: 42px; font-weight: 900; letter-spacing: 4px; color: #c8f000; font-family: 'Courier New', monospace;">
    {{ $code }}
  </div>
</div>

<!-- Info block -->
<div style="border-left: 3px solid #c8f000; background-color: #222222; padding: 14px 16px; border-radius: 0 4px 4px 0; margin-bottom: 0;">
  <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff; margin-bottom: 5px;">{{ __('Expiration') }}</div>
  <div style="font-size: 13px; color: #999999; line-height: 1.5;">{{ __('This code will expire in :minutes minutes.', ['minutes' => config('otp.expiry', 10)]) }}</div>
</div>

</x-mail.layout>
