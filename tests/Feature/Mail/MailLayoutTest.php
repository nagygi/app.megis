<?php

use App\Mail\AdminInvitationMail;
use App\Models\User;

it('frames every mail with the brand logo and the tagline footer', function () {
    $mail = new AdminInvitationMail(User::factory()->make(), User::factory()->make(), 'token');

    $mail->assertSeeInHtml(asset('images/mail/logo.png'))
        ->assertSeeInHtml('alt="'.__('ui.brand').'"', false)
        ->assertSeeInHtml(__('mail.layout.tagline'))
        ->assertDontSeeInHtml('laravel.com')
        ->assertSeeInText(__('mail.layout.tagline'))
        ->assertDontSeeInText('logo.png');
});

it('ships the logo as a PNG next to the app', function () {
    expect(public_path('images/mail/logo.png'))->toBeFile();
});
