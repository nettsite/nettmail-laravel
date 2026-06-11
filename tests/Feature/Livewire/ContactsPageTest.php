<?php

use Livewire\Livewire;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;
use NettSite\NettMail\Models\Send;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    $this->actingAs(UserFactory::new()->create());
});

it('lists contacts and supports search', function (): void {
    Contact::factory()->create(['email' => 'ada@example.test']);
    Contact::factory()->create(['email' => 'grace@example.test']);

    $this->get(route('nettmail.contacts.index'))
        ->assertSuccessful()
        ->assertSee('ada@example.test')
        ->assertSee('grace@example.test');

    Livewire::test('nettmail::contacts.index')
        ->set('search', 'ada')
        ->assertSee('ada@example.test')
        ->assertDontSee('grace@example.test');
});

it('shows contact detail with suppression state, memberships and send history', function (): void {
    $contact = Contact::factory()->create([
        'email' => 'bounced@example.test',
        'bounce_type' => BounceType::Hard,
        'bounced_at' => now(),
    ]);

    $list = MailingList::factory()->create(['name' => 'Newsletter']);
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $contact->id]);

    Send::factory()->create([
        'contact_id' => $contact->id,
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->get(route('nettmail.contacts.show', $contact))
        ->assertSuccessful()
        ->assertSee('bounced@example.test')
        ->assertSee('hard bounce')
        ->assertSee('Newsletter')
        ->assertSee('sent');
});

it('erases a contact via the erase action', function (): void {
    $contact = Contact::factory()->create(['email' => 'erase-me@example.test']);

    Livewire::test('nettmail::contacts.show', ['contact' => $contact])
        ->call('erase')
        ->assertSet('erased', true);

    $contact->refresh();
    expect($contact->email)->not->toBe('erase-me@example.test');
});
