<?php

use App\Mail\ErrorAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class AlwaysFailingJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        throw new RuntimeException('The job always fails.');
    }
}

beforeEach(function () {
    config(['megis.alerts.recipients' => ['dev@megis.hu']]);

    Route::get('/_test/failing-page', fn () => throw new RuntimeException('Lookup failed for jane.doe@example.com'));
});

it('emails the alert list when a request ends in a server error', function () {
    Mail::fake();

    $response = $this->get('/_test/failing-page');

    $response->assertInternalServerError();
    Mail::assertSent(ErrorAlert::class, function (ErrorAlert $mail): bool {
        return $mail->hasTo('dev@megis.hu')
            && $mail->errorClass === RuntimeException::class
            && $mail->errorMessage === 'Lookup failed for [e-mail]'
            && $mail->url === url('/_test/failing-page')
            && $mail->logId !== null;
    });
});

it('names the page of a failing Livewire update, not the update endpoint', function () {
    Mail::fake();
    Route::post('/_test/failing-update', fn () => throw new RuntimeException('Update failed.'));

    $this->withHeader('X-Livewire', '1')->post('/_test/failing-update', [
        'components' => [['snapshot' => json_encode(['memo' => ['path' => 'admin/inbox']])]],
    ]);

    Mail::assertSent(ErrorAlert::class, fn (ErrorAlert $mail): bool => $mail->url === url('admin/inbox'));
});

it('sends the same error only once within fifteen minutes', function () {
    Mail::fake();

    $this->get('/_test/failing-page');
    $this->travel(14)->minutes();
    $this->get('/_test/failing-page');

    Mail::assertSentCount(1);
});

it('alerts again once the throttle window has passed', function () {
    Mail::fake();

    $this->get('/_test/failing-page');
    $this->travel(16)->minutes();
    $this->get('/_test/failing-page');

    Mail::assertSentCount(2);
});

it('sends nothing when no alert recipient is configured', function () {
    Mail::fake();
    config(['megis.alerts.recipients' => []]);

    $this->get('/_test/failing-page');

    Mail::assertNothingSent();
});

it('does not alert about pages that are not found', function () {
    Mail::fake();

    $this->get('/_test/no-such-page')->assertNotFound();

    Mail::assertNothingSent();
});

it('emails the alert list when a job fails after its last attempt', function () {
    Mail::fake();

    expect(fn () => dispatch(new AlwaysFailingJob))->toThrow(RuntimeException::class);

    Mail::assertSent(ErrorAlert::class, fn (ErrorAlert $mail): bool => $mail->jobName === AlwaysFailingJob::class && $mail->url === null);
});

it('renders the alert without personal data in a fixed layout', function () {
    $mail = new ErrorAlert(
        environment: 'production',
        errorClass: RuntimeException::class,
        errorMessage: 'Lookup failed for [e-mail]',
        url: 'https://app.megis.hu/admin',
        jobName: null,
        occurredAt: now()->setDateTime(2026, 10, 14, 9, 30, 0)->toImmutable(),
        logId: 'log-123',
    );

    $mail->assertHasSubject('[production] Hiba a mégis-ben: RuntimeException');
    $mail->assertSeeInText('2026.10.14. 09:30:00');
    $mail->assertSeeInText('log-123');
    $mail->assertSeeInText('https://app.megis.hu/admin');
});
