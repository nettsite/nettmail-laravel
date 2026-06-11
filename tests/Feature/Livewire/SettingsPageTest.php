<?php

use Livewire\Livewire;
use NettSite\NettMail\Models\Sender;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    $this->actingAs(UserFactory::new()->create());
});

it('shows and saves sender, driver and bounce mailbox settings', function (): void {
    $this->get(route('nettmail.settings'))
        ->assertSuccessful();

    Livewire::test('nettmail::settings.index')
        ->set('name', 'Acme Newsletter')
        ->set('fromEmail', 'news@acme.test')
        ->set('fromName', 'Acme')
        ->set('driver', 'smtp')
        ->set('configJson', '{"host": "smtp.acme.test"}')
        ->set('bounceHost', 'imap.acme.test')
        ->set('bouncePort', '993')
        ->set('bounceUsername', 'bounces@acme.test')
        ->call('save');

    $sender = Sender::query()->sole();

    expect($sender->name)->toBe('Acme Newsletter')
        ->and($sender->from_email)->toBe('news@acme.test')
        ->and($sender->driver)->toBe('smtp')
        ->and($sender->config)->toBe(['host' => 'smtp.acme.test'])
        ->and($sender->bounce_mailbox['host'])->toBe('imap.acme.test')
        ->and($sender->bounce_mailbox['port'])->toBe(993);
});
