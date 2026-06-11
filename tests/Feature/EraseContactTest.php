<?php

use NettSite\NettMail\Facades\NettMail;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

it('anonymises a contact while preserving aggregate send statistics', function (): void {
    $contact = Contact::factory()->create([
        'email' => 'erase-me@example.test',
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'phone' => '+27123456789',
        'metadata' => ['source' => 'import'],
    ]);

    $send = Send::factory()->create(['contact_id' => $contact->id, 'status' => 'sent']);
    $event = Event::factory()->create(['send_id' => $send->id, 'type' => 'opened']);

    $erased = NettMail::eraseContact('erase-me@example.test');

    expect($erased)->toBeTrue();

    $contact->refresh();
    expect($contact->email)->not->toBe('erase-me@example.test');
    expect($contact->email)->toEndWith('@erased.invalid');
    expect($contact->first_name)->toBeNull();
    expect($contact->last_name)->toBeNull();
    expect($contact->phone)->toBeNull();
    expect($contact->metadata)->toBe([]);

    expect(Send::query()->find($send->id))->not->toBeNull();
    expect(Event::query()->find($event->id))->not->toBeNull();
    expect(Send::query()->find($send->id)->contact_id)->toBe($contact->id);
});

it('returns false when erasing an unknown email', function (): void {
    $erased = NettMail::eraseContact('does-not-exist@example.test');

    expect($erased)->toBeFalse();
});
