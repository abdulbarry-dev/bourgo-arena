<x-mail::layout>

 

<!-- Heading -->
<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ __('Welcome to') }}<br>
  <span style="color: #c8f000;">{{ __('Manager Portal') }}</span>
</div>

<!-- Body text -->
<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello,') }}<br>
  {{ __('Your manager account has been created. Use the credentials below to log in:') }}
</p>

<!-- Info block -->
<div style="border-left: 3px solid #c8f000; background-color: #222222; padding: 14px 16px; border-radius: 0 4px 4px 0; margin-bottom: 20px;">
  <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #ffffff; margin-bottom: 5px;">{{ __('Login Credentials') }}</div>
  <div style="font-size: 13px; color: #999999; line-height: 1.5;">
    <strong style="color: #c8f000;">{{ __('Email') }}:</strong> {{ $manager->email }}<br>
    <strong style="color: #c8f000;">{{ __('Temporary Password') }}:</strong> {{ $password }}
  </div>
</div>

<!-- CTA -->
<a href="{{ $resetUrl }}" style="display: block; width: 100%; background-color: #c8f000; color: #111111; font-size: 12.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; text-align: center; text-decoration: none; padding: 16px 20px; border-radius: 5px; border: none; box-sizing: border-box;">{{ __('Access Manager Portal') }}</a>

</x-mail::layout>
