<?php

use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use NettSite\NettMail\Jobs\ImportContactsCsv;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

it('imports contacts from csv and creates list memberships', function (): void {
    $list = MailingList::factory()->create();

    $csv = "Email,First,Last\nada@example.test,Ada,Lovelace\nbabbage@example.test,Charles,Babbage\n";

    $columnMap = [
        'Email' => 'email',
        'First' => 'first_name',
        'Last' => 'last_name',
    ];

    (new ImportContactsCsv($csv, $columnMap, $list->id, ['imported']))->handle(
        $this->app->make(StorageAdapterContract::class),
    );

    expect(Contact::query()->where('email', 'ada@example.test')->first()?->first_name)->toBe('Ada');

    $membership = ListContact::query()
        ->whereHas('contact', fn ($query) => $query->where('email', 'babbage@example.test'))
        ->where('list_id', $list->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->status)->toBe(MembershipStatus::Subscribed)
        ->and($membership->tags)->toBe(['imported']);
});
