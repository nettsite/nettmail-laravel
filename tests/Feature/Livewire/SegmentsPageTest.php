<?php

use Livewire\Livewire;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;
use NettSite\NettMail\Models\Segment;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    $this->actingAs(UserFactory::new()->create());
});

it('lists segments and creates a new one', function (): void {
    $list = MailingList::factory()->create(['name' => 'Newsletter']);
    Segment::factory()->create(['list_id' => $list->id, 'name' => 'Existing segment']);

    $this->get(route('nettmail.segments.index'))
        ->assertSuccessful()
        ->assertSee('Existing segment');

    Livewire::test('nettmail::segments.index')
        ->set('showForm', true)
        ->set('name', 'New segment')
        ->set('listId', $list->id)
        ->call('create')
        ->assertRedirect();

    expect(Segment::query()->where('name', 'New segment')->exists())->toBeTrue();
});

it('deletes a segment', function (): void {
    $list = MailingList::factory()->create();
    $segment = Segment::factory()->create(['list_id' => $list->id]);

    Livewire::test('nettmail::segments.index')
        ->call('delete', $segment->id);

    expect(Segment::query()->find($segment->id))->toBeNull();
});

it('builds conditions and shows a live member count', function (): void {
    $list = MailingList::factory()->create();
    $segment = Segment::factory()->create(['list_id' => $list->id, 'name' => 'VIP']);

    $matching = Contact::factory()->create(['email' => 'vip@example.test', 'first_name' => 'Vip']);
    ListContact::factory()->create([
        'list_id' => $list->id,
        'contact_id' => $matching->id,
        'status' => MembershipStatus::Subscribed,
    ]);

    $other = Contact::factory()->create(['email' => 'other@example.test', 'first_name' => 'Other']);
    ListContact::factory()->create([
        'list_id' => $list->id,
        'contact_id' => $other->id,
        'status' => MembershipStatus::Subscribed,
    ]);

    $this->get(route('nettmail.segments.show', $segment))
        ->assertSuccessful()
        ->assertSee('VIP');

    $component = Livewire::test('nettmail::segments.show', ['segment' => $segment])
        ->set('logic', 'and')
        ->call('addCondition')
        ->set('conditions.0.field', 'first_name')
        ->set('conditions.0.operator', 'is')
        ->set('conditions.0.value', 'Vip');

    expect($component->get('memberCount'))->toBe(1);

    $component->call('save');

    expect($segment->fresh()->conditions)->toBe([
        'logic' => 'and',
        'conditions' => [
            ['field' => 'first_name', 'operator' => 'is', 'value' => 'Vip'],
        ],
    ]);
});
