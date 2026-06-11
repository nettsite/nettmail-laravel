<?php

use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Domain\Contacts\OptInTokenGenerator;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

it('confirms a pending list membership', function (): void {
    $contact = Contact::factory()->create();
    $list = MailingList::factory()->create();
    $membership = ListContact::factory()->create([
        'contact_id' => $contact->id,
        'list_id' => $list->id,
        'status' => MembershipStatus::Pending,
        'subscribed_at' => null,
    ]);

    $token = $this->app->make(OptInTokenGenerator::class)
        ->generate($contact->id, $list->id, now()->addDay()->toDateTimeImmutable());

    $this->get("/nettmail/opt-in/{$token}")->assertOk();

    $membership->refresh();
    expect($membership->status)->toBe(MembershipStatus::Subscribed);
    expect($membership->subscribed_at)->not->toBeNull();
});

it('rejects an expired opt-in token', function (): void {
    $contact = Contact::factory()->create();
    $list = MailingList::factory()->create();
    ListContact::factory()->create([
        'contact_id' => $contact->id,
        'list_id' => $list->id,
        'status' => MembershipStatus::Pending,
        'subscribed_at' => null,
    ]);

    $token = $this->app->make(OptInTokenGenerator::class)
        ->generate($contact->id, $list->id, now()->subDay()->toDateTimeImmutable());

    $this->get("/nettmail/opt-in/{$token}")->assertStatus(410);
});

it('returns 404 when no membership exists for the token', function (): void {
    $contact = Contact::factory()->create();
    $list = MailingList::factory()->create();

    $token = $this->app->make(OptInTokenGenerator::class)
        ->generate($contact->id, $list->id, now()->addDay()->toDateTimeImmutable());

    $this->get("/nettmail/opt-in/{$token}")->assertStatus(404);
});
