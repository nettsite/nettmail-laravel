<?php

use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeTokenGenerator;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

it('unsubscribes a contact from a specific list on GET', function (): void {
    $contact = Contact::factory()->create();
    $list = MailingList::factory()->create();
    $membership = ListContact::factory()->create([
        'contact_id' => $contact->id,
        'list_id' => $list->id,
        'status' => MembershipStatus::Subscribed,
    ]);

    $token = $this->app->make(UnsubscribeTokenGenerator::class)->generate($contact->id, $list->id);

    $this->get("/nettmail/unsubscribe/{$token}")->assertOk();

    $membership->refresh();
    expect($membership->status)->toBe(MembershipStatus::Unsubscribed);
    expect($membership->unsubscribed_at)->not->toBeNull();

    $contact->refresh();
    expect($contact->global_unsubscribed_at)->toBeNull();
});

it('unsubscribes a contact from everything when the token has no list id', function (): void {
    $contact = Contact::factory()->create();

    $token = $this->app->make(UnsubscribeTokenGenerator::class)->generate($contact->id, null);

    $this->get("/nettmail/unsubscribe/{$token}")->assertOk();

    $contact->refresh();
    expect($contact->global_unsubscribed_at)->not->toBeNull();
});

it('unsubscribes from all via the unsubscribe-all link', function (): void {
    $contact = Contact::factory()->create();
    $list = MailingList::factory()->create();

    $token = $this->app->make(UnsubscribeTokenGenerator::class)->generate($contact->id, $list->id);

    $this->get("/nettmail/unsubscribe/{$token}/all")->assertOk();

    $contact->refresh();
    expect($contact->global_unsubscribed_at)->not->toBeNull();
});

it('rejects a tampered unsubscribe token', function (): void {
    $this->get('/nettmail/unsubscribe/not-a-real-token')->assertStatus(404);
});

it('handles the RFC 8058 one-click POST without rendering a page', function (): void {
    $contact = Contact::factory()->create();
    $list = MailingList::factory()->create();
    $membership = ListContact::factory()->create([
        'contact_id' => $contact->id,
        'list_id' => $list->id,
        'status' => MembershipStatus::Subscribed,
    ]);

    $token = $this->app->make(UnsubscribeTokenGenerator::class)->generate($contact->id, $list->id);

    $this->post("/nettmail/unsubscribe/{$token}")->assertOk()->assertContent('');

    $membership->refresh();
    expect($membership->status)->toBe(MembershipStatus::Unsubscribed);
});
