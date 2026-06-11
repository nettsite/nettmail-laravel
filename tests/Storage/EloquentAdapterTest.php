<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Contacts\ListMembership;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use NettSite\NettMail\Storage\EloquentAdapter;

it('dedupes contacts by normalised email on save', function () {
    $adapter = new EloquentAdapter;

    $first = $adapter->saveContact(new Contact(id: null, email: 'Jane@Example.com', firstName: 'Jane'));
    $second = $adapter->saveContact(new Contact(id: null, email: 'jane@example.com ', firstName: 'Janet'));

    expect($second->id)->toBe($first->id)
        ->and($adapter->findContactByEmail('JANE@EXAMPLE.COM')?->firstName)->toBe('Janet');
});

it('finds contacts by id', function () {
    $adapter = new EloquentAdapter;

    $saved = $adapter->saveContact(new Contact(id: null, email: 'jane@example.com'));

    expect($adapter->findContactById($saved->id)?->email)->toBe($saved->email)
        ->and($adapter->findContactById('01979b6a-0000-7000-8000-000000000000'))->toBeNull();
});

it('saves and finds lists by id and slug', function () {
    $adapter = new EloquentAdapter;

    $list = $adapter->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    expect($adapter->findListById($list->id)?->slug)->toBe('newsletter')
        ->and($adapter->findListBySlug('newsletter')?->id)->toBe($list->id)
        ->and($adapter->findListBySlug('missing'))->toBeNull();
});

it('saves and finds list memberships', function () {
    $adapter = new EloquentAdapter;

    $contact = $adapter->saveContact(new Contact(id: null, email: 'jane@example.com'));
    $list = $adapter->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    $membership = $adapter->saveMembership(new ListMembership(
        contactId: $contact->id,
        listId: $list->id,
        status: MembershipStatus::Pending,
    ));

    $found = $adapter->findMembership($contact->id, $list->id);

    expect($found->status)->toBe(MembershipStatus::Pending)
        ->and($membership->status)->toBe(MembershipStatus::Pending)
        ->and($adapter->findMembership($contact->id, '01979b6a-0000-7000-8000-000000000000'))->toBeNull();
});

it('finds only suppressed contacts', function () {
    $adapter = new EloquentAdapter;

    $active = $adapter->saveContact(new Contact(id: null, email: 'active@example.com'));
    $bounced = $adapter->saveContact(new Contact(id: null, email: 'bounced@example.com', bounceType: BounceType::Hard));

    $suppressed = $adapter->findSuppressedContacts();

    expect($suppressed)->toHaveCount(1)
        ->and($suppressed[0]->id)->toBe($bounced->id)
        ->and($active)->not->toBeNull();
});
