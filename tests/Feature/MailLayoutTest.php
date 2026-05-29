<?php

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