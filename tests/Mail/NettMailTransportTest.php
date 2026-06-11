<?php

use Illuminate\Support\Facades\Mail;
use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Send;
use NettSite\NettMail\Tests\Fakes\FakeMailDriver;
use NettSite\NettMail\Tests\Fixtures\Mail\PlainMailable;
use NettSite\NettMail\Tests\Fixtures\Mail\TrackedMailable;

beforeEach(function (): void {
    config()->set('mail.default', 'nettmail');
    config()->set('mail.mailers.nettmail', ['transport' => 'nettmail']);
    config()->set('mail.from', ['address' => 'sender@example.test', 'name' => 'Sender']);
    config()->set('app.url', 'https://app.test');

    $this->fakeDriver = new FakeMailDriver;
    $this->app->bind(MailDriverContract::class, fn () => $this->fakeDriver);
});

it('logs a transactional send and upserts the recipient contact', function (): void {
    Mail::to('recipient@example.test')->send(new PlainMailable);

    expect($this->fakeDriver->lastMessage)->not->toBeNull();
    expect($this->fakeDriver->lastMessage->to[0]->email)->toBe('recipient@example.test');

    $contact = Contact::query()->where('email', 'recipient@example.test')->first();
    expect($contact)->not->toBeNull();

    $send = Send::query()->where('contact_id', $contact->id)->first();
    expect($send)->not->toBeNull();
    expect($send->status)->toBe('sent');
    expect($send->provider_message_id)->toBe('fake-message-id@example.test');
    expect($send->sent_at)->not->toBeNull();
});

it('blocks sends to complaint-flagged contacts', function (): void {
    $contact = Contact::factory()->create([
        'email' => 'complainer@example.test',
        'bounce_type' => BounceType::Complaint,
    ]);

    Mail::to('complainer@example.test')->send(new PlainMailable);

    expect($this->fakeDriver->lastMessage)->toBeNull();

    $send = Send::query()->where('contact_id', $contact->id)->first();
    expect($send)->not->toBeNull();
    expect($send->status)->toBe('suppressed');
    expect($send->provider_message_id)->toBeNull();
});

it('leaves a plain mailable untouched by tracking', function (): void {
    Mail::to('plain@example.test')->send(new PlainMailable);

    $sentHtml = $this->fakeDriver->lastMessage->html;

    expect($sentHtml)->not->toContain('track/open');
    expect($sentHtml)->not->toContain('track/click');
    expect($sentHtml)->toContain('https://example.com');
});

it('inserts open pixel and rewrites links for a tracked mailable', function (): void {
    Mail::to('tracked@example.test')->send(new TrackedMailable);

    $contact = Contact::query()->where('email', 'tracked@example.test')->first();
    $send = Send::query()->where('contact_id', $contact->id)->first();

    $sentHtml = $this->fakeDriver->lastMessage->html;

    expect($sentHtml)->toContain('track/open/'.$send->send_token);
    expect($sentHtml)->toContain('track/click/'.$send->send_token);
    expect($sentHtml)->not->toContain('https://example.com');

    expect($send->transactional_key)->toBe('welcome-email');

    $headerNames = array_keys($this->fakeDriver->lastMessage->headers);
    expect($headerNames)->not->toContain('X-NettMail-Track-Opens');
    expect($headerNames)->not->toContain('X-NettMail-Transactional-Key');
});
