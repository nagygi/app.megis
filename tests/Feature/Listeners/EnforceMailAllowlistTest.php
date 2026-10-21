<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;

/**
 * @return Collection<int, SentMessage>
 */
function deliveredMessages(): Collection
{
    return app('mailer')->getSymfonyTransport()->messages();
}

/**
 * @return list<string>
 */
function deliveredRecipients(): array
{
    return array_map(
        fn (Address $address): string => $address->getAddress(),
        deliveredMessages()->first()->getOriginalMessage()->getTo(),
    );
}

it('delivers only to allowlisted addresses and domains outside production', function () {
    config(['megis.mail_allowlist' => ['@megis.hu', 'tester@example.com']]);

    Mail::raw('Teszt', fn ($message) => $message->to(['luca@megis.hu', 'customer@example.com', 'tester@example.com']));

    expect(deliveredRecipients())->toBe(['luca@megis.hu', 'tester@example.com']);
});

it('drops a message that has no allowlisted recipient and logs only the domains', function () {
    config(['megis.mail_allowlist' => ['@megis.hu']]);
    $logged = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
        $logged[] = $event;
    });

    Mail::raw('Teszt', fn ($message) => $message->subject('Meghívó')->to(['customer@example.com', 'Luca@Gmail.com'])->cc('other@example.com'));

    expect(deliveredMessages())->toBeEmpty()
        ->and($logged)->toHaveCount(1)
        ->and($logged[0]->message)->toBe('Mail suppressed: no allowlisted recipient.')
        ->and($logged[0]->context)->toBe(['subject' => 'Meghívó', 'recipient_domains' => ['example.com', 'gmail.com']]);
});

it('delivers to everyone when no allowlist is configured', function () {
    config(['megis.mail_allowlist' => []]);

    Mail::raw('Teszt', fn ($message) => $message->to('customer@example.com'));

    expect(deliveredRecipients())->toBe(['customer@example.com']);
});

it('never filters mail in production', function () {
    $this->app['env'] = 'production';
    config(['megis.mail_allowlist' => ['@megis.hu']]);

    Mail::raw('Teszt', fn ($message) => $message->to('customer@example.com'));

    expect(deliveredRecipients())->toBe(['customer@example.com']);
});
