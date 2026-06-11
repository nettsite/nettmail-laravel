<?php

use NettSite\NettMail\Console\Commands\PollBounceMailboxCommand;
use Nettsite\NettMail\Core\Contracts\MailboxContract;
use Nettsite\NettMail\Core\Domain\Bounces\MailboxMessage;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Send;
use NettSite\NettMail\Tests\Fakes\FakeMailbox;

function bounceFixture(string $name): string
{
    return file_get_contents(__DIR__.'/../Fixtures/Bounces/'.$name);
}

it('processes a hard bounce and suppresses the contact', function (): void {
    $contact = Contact::factory()->create(['email' => 'nobody@invalid-domain.test']);

    $mailbox = new FakeMailbox([
        new MailboxMessage('1', bounceFixture('hard-bounce.eml')),
    ]);
    $this->app->bind(MailboxContract::class, fn () => $mailbox);

    $this->artisan(PollBounceMailboxCommand::class)->assertSuccessful();

    $contact->refresh();
    expect($contact->bounce_type)->toBe(BounceType::Hard);
    expect($contact->bounced_at)->not->toBeNull();

    expect($mailbox->movedTo)->toBe(['1' => 'Processed']);
});

it('moves unrecognised messages to the unrecognised folder', function (): void {
    $mailbox = new FakeMailbox([
        new MailboxMessage('1', bounceFixture('unrecognised.eml')),
    ]);
    $this->app->bind(MailboxContract::class, fn () => $mailbox);

    $this->artisan(PollBounceMailboxCommand::class)->assertSuccessful();

    expect($mailbox->movedTo)->toBe(['1' => 'Unrecognised']);
});

it('resets stale soft bounce counters after a successful send', function (): void {
    $contact = Contact::factory()->create([
        'bounce_type' => BounceType::Soft,
        'bounced_at' => now()->subDays(10),
        'consecutive_soft_bounces' => 1,
    ]);

    Send::factory()->create([
        'contact_id' => $contact->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(9),
    ]);

    $this->app->bind(MailboxContract::class, fn () => new FakeMailbox);

    $this->artisan(PollBounceMailboxCommand::class)->assertSuccessful();

    $contact->refresh();
    expect($contact->bounce_type)->toBeNull();
    expect($contact->bounced_at)->toBeNull();
    expect($contact->consecutive_soft_bounces)->toBe(0);
});

it('does not reset a soft bounce when the last send is too recent', function (): void {
    $contact = Contact::factory()->create([
        'bounce_type' => BounceType::Soft,
        'bounced_at' => now()->subDays(10),
        'consecutive_soft_bounces' => 1,
    ]);

    Send::factory()->create([
        'contact_id' => $contact->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(2),
    ]);

    $this->app->bind(MailboxContract::class, fn () => new FakeMailbox);

    $this->artisan(PollBounceMailboxCommand::class)->assertSuccessful();

    $contact->refresh();
    expect($contact->bounce_type)->toBe(BounceType::Soft);
});
