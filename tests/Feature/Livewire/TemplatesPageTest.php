<?php

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use NettSite\NettMail\Models\Template;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    $this->actingAs(UserFactory::new()->create());
});

it('lists templates and creates a new one', function (): void {
    Template::factory()->create(['name' => 'Existing template']);

    $this->get(route('nettmail.templates.index'))
        ->assertSuccessful()
        ->assertSee('Existing template');

    Livewire::test('nettmail::templates.index')
        ->set('showForm', true)
        ->set('name', 'New template')
        ->set('type', 'broadcast')
        ->call('create')
        ->assertRedirect();

    expect(Template::query()->where('name', 'New template')->exists())->toBeTrue();
});

it('duplicates and archives a template', function (): void {
    $template = Template::factory()->create(['name' => 'Original']);

    Livewire::test('nettmail::templates.index')
        ->call('duplicate', $template->id);

    expect(Template::query()->where('name', 'Original (copy)')->exists())->toBeTrue();

    Livewire::test('nettmail::templates.index')
        ->call('archive', $template->id);

    expect($template->fresh()->archived_at)->not->toBeNull();

    Livewire::test('nettmail::templates.index')
        ->set('showArchived', true)
        ->assertSee('Original');
});

it('warns about a missing unsubscribe block on broadcast templates', function (): void {
    $template = Template::factory()->create(['type' => 'broadcast', 'html' => '<p>Hello</p>']);

    Livewire::test('nettmail::templates.show', ['template' => $template])
        ->assertSet('missingUnsubscribeLink', true)
        ->set('html', '<p>Hello {{unsubscribe_url}}</p>')
        ->assertSet('missingUnsubscribeLink', false)
        ->call('save');

    expect($template->fresh()->plain_text)->toContain('Hello');
});

it('renders a preview with sample merge tag values', function (): void {
    $template = Template::factory()->create(['html' => '<p>Hi {{first_name}}, {{unsubscribe_url}}</p>']);

    Livewire::test('nettmail::templates.show', ['template' => $template])
        ->assertSee('Hi Jamie');
});

it('sends a test email', function (): void {
    Mail::fake();

    $template = Template::factory()->create(['subject' => 'Hello', 'html' => '<p>Hi {{first_name}}, {{unsubscribe_url}}</p>']);

    Livewire::test('nettmail::templates.show', ['template' => $template])
        ->set('testEmail', 'test@example.test')
        ->call('sendTest');

    Mail::assertSent(function (Mailable $mail): bool {
        return $mail->hasTo('test@example.test');
    });
});
