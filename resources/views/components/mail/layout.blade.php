<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #222222;
            border: 1px solid #333333;
        }

        .email-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 2px solid #C8FF00;
        }

        .email-header-logo {
            font-size: 24px;
            font-weight: bold;
            color: #C8FF00;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .email-header-logo span {
            color: #ffffff;
        }

        .email-content {
            padding: 40px 30px;
        }

        .email-content h1 {
            color: #C8FF00;
            font-size: 28px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .email-content h2 {
            color: #C8FF00;
            font-size: 20px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .email-content p {
            color: #e0e0e0;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.8;
        }

        .email-content strong {
            color: #C8FF00;
        }

        .button {
            display: inline-block;
            background-color: #C8FF00;
            color: #000000;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .button:hover {
            background-color: #b8ef00;
            text-decoration: none;
            color: #000000;
        }

        .button-wrapper {
            text-align: center;
            margin: 30px 0;
        }

        .info-box {
            background-color: #2a2a2a;
            border-left: 4px solid #C8FF00;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-box p {
            color: #d0d0d0;
            font-size: 13px;
            margin: 0;
        }

        .info-box-title {
            color: #C8FF00;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .divider {
            height: 1px;
            background-color: #333333;
            margin: 30px 0;
        }

        .email-footer {
            background-color: #1a1a1a;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #333333;
        }

        .email-footer p {
            color: #888888;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .email-footer a {
            color: #C8FF00;
            text-decoration: none;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        .footer-brand {
            color: #666666;
            font-size: 11px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #333333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table td {
            padding: 12px 0;
            border-bottom: 1px solid #333333;
            color: #e0e0e0;
        }

        table td:first-child {
            font-weight: 600;
            color: #C8FF00;
            width: 40%;
        }

        .success-check {
            display: inline-block;
            width: 50px;
            height: 50px;
            background-color: #C8FF00;
            border-radius: 50%;
            text-align: center;
            line-height: 50px;
            color: #000000;
            font-weight: bold;
            font-size: 28px;
            margin: 20px 0;
        }

        .code-block {
            background-color: #2a2a2a;
            border: 1px solid #C8FF00;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
            margin: 20px 0;
        }

        .code-block .code {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            color: #C8FF00;
            letter-spacing: 3px;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #888888;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="email-header-logo">BOURGO<span>ARENA</span></div>
        </div>

        <!-- Content -->
        <div class="email-content">
            {{ $slot }}
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>{{ config('app.name') }} - {{ __('All your sports needs in one place') }}</p>
            <p class="footer-brand">© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
        </div>
    </div>
</body>
</html>
