<?php

use Illuminate\Mail\SentMessage;
use Illuminate\Support\Collection;
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

it('drops a message that has no allowlisted recipient', function () {
    config(['megis.mail_allowlist' => ['@megis.hu']]);

    Mail::raw('Teszt', fn ($message) => $message->to('customer@example.com'));

    expect(deliveredMessages())->toBeEmpty();
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
