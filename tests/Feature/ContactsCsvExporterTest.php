<?php

use NettSite\NettMail\Contacts\ContactsCsvExporter;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use NettSite\NettMail\Facades\NettMail;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

it('exports list members as csv', function (): void {
    $list = MailingList::factory()->create();
    $contact = Contact::factory()->create(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);

    ListContact::factory()->create([
        'list_id' => $list->id,
        'contact_id' => $contact->id,
        'status' => MembershipStatus::Subscribed,
        'tags' => ['vip'],
    ]);

    $csv = (new ContactsCsvExporter)->exportList($list);

    expect($csv)->toContain('email,first_name,last_name,phone,status,tags,subscribed_at')
        ->and($csv)->toContain('ada@example.test,Ada,Lovelace,,subscribed,vip,');
});

it('exports globally suppressed contacts via the core suppression format', function (): void {
    Contact::factory()->create([
        'email' => 'bounced@example.test',
        'global_unsubscribed_at' => now(),
    ]);

    $csv = NettMail::exportSuppressions();

    expect($csv)->toContain('email,reason,suppressed_at')
        ->and($csv)->toContain('bounced@example.test,unsubscribed,');
});
