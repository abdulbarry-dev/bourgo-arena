@component('mail::message')
# {{ __('Welcome to :app', ['app' => config('app.name')]) }}

{{ __('Hello :name,', ['name' => $member->name]) }}

{{ __('Welcome! Your account has been created and is ready to use.') }}

{{ __('Below are your temporary login details:') }}

**{{ __('Email') }}:** {{ $member->fallback_email }}<br>
**{{ __('Temporary Password') }}:** {{ $temporaryPassword }}

{{ __('Please set up your new secure password using the link below:') }}

@component('mail::button', ['url' => $onboardingUrl])
{{ __('Complete Account Setup') }}
@endcomponent

{{ __('This link expires on :date.', ['date' => $expiresAt]) }}

{{ __('For your security, do not share this email with anyone.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
