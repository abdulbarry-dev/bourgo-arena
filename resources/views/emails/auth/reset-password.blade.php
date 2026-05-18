@component('mail::message')
# {{ __('Reset Your Password') }}

{{ __('Hello') }}{{ $userName ? ' ' . $userName : '' }},

{{ __('You have requested to reset your password. Click the button below to proceed:') }}

@component('mail::button', ['url' => $resetUrl])
{{ __('Reset Password') }}
@endcomponent

{{ __('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire', 60)]) }}

{{ __('If you did not request a password reset, no further action is required.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
