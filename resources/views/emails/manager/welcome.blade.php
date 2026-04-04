@component('mail::message')
# Welcome to {{ config('app.name') }}!

Hello {{ $manager->name }},

You have been invited to join the management team.

Below are your temporary login details:

**Email:** {{ $manager->email }}<br>
**Password:** {{ $password }}

Please log in and update your password immediately.

@component('mail::button', ['url' => route('login')])
Log In
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
