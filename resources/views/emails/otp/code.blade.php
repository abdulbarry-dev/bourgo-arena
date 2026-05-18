@component('mail::message')
# {{ __('Your OTP Verification Code') }}

{{ __('Hello') }}{{ $userName ? ' ' . $userName : '' }},

{{ __('Your verification code is:') }}

@component('mail::button', ['url' => '#'])
{{ $code }}
@endcomponent

{{ __('This code will expire in :minutes minutes.', ['minutes' => config('otp.expiry', 10)]) }}

{{ __('If you did not request this code, no further action is required.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
