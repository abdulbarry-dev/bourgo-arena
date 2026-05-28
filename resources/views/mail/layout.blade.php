<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ config('app.name') }} – Confirmation</title>
</head>
<body style="background-color: #111111; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; margin: 0;">

  <div style="width: 100%; max-width: 520px;">

    <!-- Header -->
    <div style="text-align: center; margin-bottom: 12px;">
        <div style="display: inline-flex; align-items: baseline; gap: 0; font-size: 22px; font-weight: 900; letter-spacing: 0.06em; text-transform: uppercase;">
        <span style="color: #ffffff;">{{ __('Bourgo Arena') }}</span>
      </div>
      <div style="font-size: 9px; letter-spacing: 0.25em; color: #888888; text-transform: uppercase; margin-top: 4px;">{{ __('Le QG du Sport à Djerba') }}</div>
    </div>

    <!-- Card -->
    <div style="background-color: #1c1c1c; border-radius: 6px; padding: 44px 40px 40px; margin-top: 18px;">

      {{ $slot }}

    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 28px;">
      <div style="font-size: 11px; color: #555555; line-height: 1.7;">
        {{ __('Bourgo Arena Complex, Djerba, Tunisie') }}<br>
        {{ __('Le QG du Sport à Djerba') }}
      </div>
      <div style="margin-top: 10px; font-size: 11px;">
        <a href="#" style="color: #c8f000; text-decoration: none; margin: 0 4px;">{{ __('Préférences') }}</a> · <a href="#" style="color: #c8f000; text-decoration: none; margin: 0 4px;">{{ __('Se désabonner') }}</a>
      </div>
      <div style="margin-top: 8px; font-size: 10px; color: #3d3d3d; letter-spacing: 0.03em;">{{ __('Mentions légales · Confidentialité · Contact') }}</div>
    </div>

  </div>

</body>
</html>
