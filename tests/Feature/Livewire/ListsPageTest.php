<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use NettSite\NettMail\Jobs\ImportContactsCsv;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    $this->actingAs(UserFactory::new()->create());
});

it('lists mailing lists and creates a new one', function (): void {
    MailingList::factory()->create(['name' => 'Existing list']);

    $this->get(route('nettmail.lists.index'))
        ->assertSuccessful()
        ->assertSee('Existing list');

    Livewire::test('nettmail::lists.index')
        ->set('showForm', true)
        ->set('name', 'New list')
        ->call('create')
        ->assertSet('showForm', false);

    expect(MailingList::query()->where('name', 'New list')->exists())->toBeTrue();
});

it('deletes a mailing list and its memberships', function (): void {
    $list = MailingList::factory()->create();
    $contact = Contact::factory()->create();
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $contact->id]);

    Livewire::test('nettmail::lists.index')
        ->call('delete', $list->id);

    expect(MailingList::query()->find($list->id))->toBeNull();
    expect(ListContact::query()->where('list_id', $list->id)->exists())->toBeFalse();
});

it('shows list members and saves details', function (): void {
    $list = MailingList::factory()->create(['name' => 'Newsletter']);
    $contact = Contact::factory()->create(['email' => 'member@example.test']);
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $contact->id]);

    $this->get(route('nettmail.lists.show', $list))
        ->assertSuccessful()
        ->assertSee('member@example.test');

    Livewire::test('nettmail::lists.show', ['list' => $list])
        ->set('name', 'Renamed list')
        ->call('save');

    expect($list->fresh()->name)->toBe('Renamed list');
});

it('runs the csv import wizard with column mapping', function (): void {
    Bus::fake();

    $list = MailingList::factory()->create();

    $csv = "Email,First\nimported@example.test,Imported\n";
    $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

    Livewire::test('nettmail::lists.show', ['list' => $list])
        ->set('csvFile', $file)
        ->call('uploadCsv')
        ->assertSet('importStep', 2)
        ->set('columnMap.Email', 'email')
        ->set('columnMap.First', 'first_name')
        ->set('tagsInput', 'imported')
        ->call('startImport')
        ->assertSet('importStep', 1);

    Bus::assertDispatched(ImportContactsCsv::class);
});
