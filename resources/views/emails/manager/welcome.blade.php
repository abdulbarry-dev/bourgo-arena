@component('mail::message')
# Welcome to {{ config('app.name') }}!

Hello {{ $manager->name }},

You have been invited to join the management team.

Below are your temporary login details:

**Email:** {{ $manager->email }}<br>
**Password:** {{ $password }}

Please set up your new secure password using the link below:

@component('mail::button', ['url' => $resetUrl])
Reset Password & Log In
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
