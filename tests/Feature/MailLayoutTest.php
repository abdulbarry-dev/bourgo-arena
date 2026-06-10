<?php

use App\Mail\SendOtpCodeMail;

it('renders a bourgo arena email through the shared mail shell', function () {
    $html = view('emails.otp.code', [
        'code' => '883971',
        'userName' => 'Mohamed',
    ])->render();

    expect($html)
        ->toContain('BOURGO')
        ->toContain('ARENA')
        ->toContain('Le QG du Sport à Djerba')
        ->toContain('Bourgo Arena Complex, Djerba, Tunisie')
        ->toContain('background-color: #111111')
        ->toContain('background-color: #1c1c1c')
        ->toContain('883971');
});

// ─── SendOtpCodeMail ───

it('send otp code mail envelope has correct to and subject', function () {
    $mail = new SendOtpCodeMail(
        code: '123456',
        userEmail: 'user@example.com',
        userName: 'Ahmed',
    );

    $envelope = $mail->envelope();

    expect($envelope->to[0]->address)->toBe('user@example.com');
    expect($envelope->subject)->toBe('Your OTP Verification Code');
});

it('send otp code mail content uses correct markdown template', function () {
    $mail = new SendOtpCodeMail(
        code: '123456',
        userEmail: 'user@example.com',
    );

    $content = $mail->content();

    expect($content->markdown)->toBe('emails.otp.code');
});

it('send otp code mail rendered html contains code and user name', function () {
    $mail = new SendOtpCodeMail(
        code: '987654',
        userEmail: 'user@example.com',
        userName: 'Mohamed',
    );

    $html = view('emails.otp.code', [
        'code' => $mail->code,
        'userName' => $mail->userName,
    ])->render();

    expect($html)
        ->toContain('987654')
        ->toContain('Mohamed')
        ->toContain('Your Verification');
});

it('send otp code mail handles null userName gracefully', function () {
    $mail = new SendOtpCodeMail(
        code: '123456',
        userEmail: 'user@example.com',
    );

    $html = view('emails.otp.code', [
        'code' => $mail->code,
        'userName' => $mail->userName,
    ])->render();

    expect($html)->toContain('123456');
});
