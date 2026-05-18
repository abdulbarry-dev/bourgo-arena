<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#111111;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#111111;width:100%;">
    <tr>
      <td align="center" style="padding:40px 20px;">

        <!-- Wrapper -->
        <table width="520" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;max-width:520px;">

          <!-- Header -->
          <tr>
            <td style="text-align:center;padding-bottom:12px;">
              <div style="display:inline-block;font-size:22px;font-weight:900;letter-spacing:0.06em;text-transform:uppercase;color:#ffffff;">
                BOURGO<span style="color:#c8f000">ARENA</span>
              </div>
              <div style="font-size:9px;color:#888888;letter-spacing:0.25em;text-transform:uppercase;margin-top:4px;">Le QG du Sport à Djerba</div>
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td>
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#1c1c1c;border-radius:6px;overflow:hidden;">
                <tr>
                  <td style="padding:44px 40px;">

                    <!-- Slot content -->
                    {!! $slot !!}

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding-top:18px;text-align:center;">
              <div style="font-size:11px;color:#555555;line-height:1.7;">Bourgo Arena Complex, Djerba, Tunisie<br>Le QG du Sport à Djerba</div>
              <div style="margin-top:10px;font-size:11px;">
                <a href="#" style="color:#c8f000;text-decoration:none;margin:0 4px;">Préférences</a> · <a href="#" style="color:#c8f000;text-decoration:none;margin:0 4px;">Se désabonner</a>
              </div>
              <div style="margin-top:8px;font-size:10px;color:#3d3d3d;letter-spacing:0.03em;">Mentions légales · Confidentialité · Contact</div>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>