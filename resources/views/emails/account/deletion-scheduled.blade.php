@component('mail::message')
# {{ __('Account Deletion Scheduled') }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __('We received a request to delete your account.') }}

{{ __('Your account is scheduled for deletion in 48 hours.') }}

{{ __('If you did not request this, or if you change your mind, you can cancel this process simply by logging back into your account before the 48-hour window expires.') }}

{{ __('Note: Logging in will require an OTP verification to confirm your identity and cancel the deletion.') }}

{{ __('Thank you for being with us!') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
