@component('mail::message')
# {{ __('Your NFC Card Has Been Assigned') }}

{{ __('Hello :name,', ['name' => $member->name]) }}

{{ __('Your :app NFC card has been successfully assigned to your account.', ['app' => config('app.name')]) }}

**{{ __('Card UID') }}:** {{ $member->nfcCard?->uid ?? __('N/A') }}<br>
**{{ __('Status') }}:** {{ ucfirst($member->nfcCard?->status ?? __('N/A')) }}

{{ __('You can now use your card to access all :app services.', ['app' => config('app.name')]) }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
