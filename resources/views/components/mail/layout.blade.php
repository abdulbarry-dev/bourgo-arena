@props([
    'title' => config('app.name') . ' – Confirmation',
    'tagline' => __('Le QG du Sport à Djerba'),
    'address' => __('Bourgo Arena Complex, Djerba, Tunisie'),
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #111111; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;" bgcolor="#111111">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#111111" style="width: 100%; background-color: #111111; border-collapse: collapse;">
    <tr>
        <td align="center" bgcolor="#111111" style="padding: 40px 20px; background-color: #111111;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" bgcolor="#111111" style="width: 100%; max-width: 520px; border-collapse: collapse; background-color: #111111;">
                <tr>
                    <td align="center" style="padding-bottom: 12px;">
                        <div style="display: inline-block; font-size: 22px; font-weight: 900; letter-spacing: 0.06em; text-transform: uppercase; line-height: 1;">
                            <span style="color: #ffffff;">BOURGO</span><span style="color: #c8f000;"> ARENA</span>
                        </div>
                        <div style="font-size: 9px; letter-spacing: 0.25em; color: #888888; text-transform: uppercase; margin-top: 4px; line-height: 1.4;">{{ $tagline }}</div>
                    </td>
                </tr>
                <tr>
                    <td bgcolor="#1c1c1c" style="background-color: #1c1c1c; border-radius: 6px; padding: 44px 40px 40px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td>
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="46" height="46" style="width: 46px; height: 46px; border-collapse: collapse; background-color: #2a2a2a; border-radius: 10px; margin-bottom: 24px;">
                                        <tr>
                                            <td align="center" valign="middle">
                                                @isset($icon)
                                                    {{ $icon }}
                                                @else
                                                    <div style="width: 14px; height: 14px; background-color: #c8f000; border-radius: 50%; display: inline-block;"></div>
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>

                                    {{ $slot }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 28px; text-align: center;">
                        <div style="font-size: 11px; color: #555555; line-height: 1.7;">
                            {{ $address }}<br>
                            {{ $tagline }}
                        </div>
                        <div style="margin-top: 10px; font-size: 11px; line-height: 1.5;">
                            <a href="#" style="color: #c8f000; text-decoration: none;">{{ __('Préférences') }}</a>
                            <span style="color: #555555;"> · </span>
                            <a href="#" style="color: #c8f000; text-decoration: none;">{{ __('Se désabonner') }}</a>
                        </div>
                        <div style="margin-top: 8px; font-size: 10px; color: #3d3d3d; letter-spacing: 0.03em; line-height: 1.5;">{{ __('Mentions légales') }} · {{ __('Confidentialité') }} · {{ __('Contact') }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
