<?php

use Illuminate\Support\Facades\Mail;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Domain\Contacts\OptInTokenGenerator;
use NettSite\NettMail\Mail\OptInConfirmationMail;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

it('sends a confirmation email when added as pending to a double opt-in list', function (): void {
    Mail::fake();

    $list = MailingList::factory()->create(['double_optin' => true]);
    $contact = Contact::factory()->create();

    ListContact::factory()->create([
        'list_id' => $list->id,
        'contact_id' => $contact->id,
        'status' => MembershipStatus::Pending,
        'subscribed_at' => null,
    ]);

    Mail::assertSent(OptInConfirmationMail::class, function (OptInConfirmationMail $mail) use ($contact, $list): bool {
        return $mail->contact->is($contact) && $mail->list->is($list);
    });
});

it('does not send a confirmation email for non double opt-in lists', function (): void {
    Mail::fake();

    $list = MailingList::factory()->create(['double_optin' => false]);
    $contact = Contact::factory()->create();

    ListContact::factory()->create([
        'list_id' => $list->id,
        'contact_id' => $contact->id,
        'status' => MembershipStatus::Pending,
        'subscribed_at' => null,
    ]);

    Mail::assertNotSent(OptInConfirmationMail::class);
});

it('confirms the membership when the opt-in link is visited', function (): void {
    $list = MailingList::factory()->create(['double_optin' => true]);
    $contact = Contact::factory()->create();

    $membership = ListContact::factory()->create([
        'list_id' => $list->id,
        'contact_id' => $contact->id,
        'status' => MembershipStatus::Pending,
        'subscribed_at' => null,
    ]);

    $token = $this->app->make(OptInTokenGenerator::class)
        ->generate($contact->id, $list->id, new DateTimeImmutable('+24 hours'));

    $this->get("/nettmail/opt-in/{$token}")->assertOk();

    expect($membership->refresh()->status)->toBe(MembershipStatus::Subscribed);
});
