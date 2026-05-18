<x-mail::layout>

 

<!-- Heading -->
<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ __('Account Deletion') }}<br>
  <span style="color: #c8f000;">{{ __('Scheduled') }}</span>
</div>

<!-- Body text -->
<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello :name,', ['name' => $user->name]) }}<br>
  {{ __('We received a request to delete your account. Your account is scheduled for deletion in 48 hours.') }}
</p>

<!-- Info block -->
<div style="border-left: 3px solid #c8f000; background-color: #222222; padding: 14px 16px; border-radius: 0 4px 4px 0; margin-bottom: 32px;">
  <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff; margin-bottom: 5px;">{{ __('Important') }}</div>
  <div style="font-size: 13px; color: #999999; line-height: 1.5;">{{ __('If you did not request this, you can cancel the deletion by logging back into your account before the 48-hour window expires.') }}</div>
</div>

</x-mail::layout>
