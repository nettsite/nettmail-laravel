<?php

use NettSite\NettMail\Contacts\ContactSourceRegistry;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Tests\Fakes\FakeContactSource;

it('syncs contacts from a registered source', function (): void {
    $source = new FakeContactSource([
        ['email' => 'ada@example.test', 'first_name' => 'Ada', 'source_id' => '1'],
        ['email' => 'not-an-email', 'first_name' => 'Invalid'],
    ]);

    $this->app->make(ContactSourceRegistry::class)->register($source);

    $this->artisan('nettmail:sync-contacts', ['source' => 'fake'])
        ->expectsOutputToContain('Created 1, updated 0, skipped 1 invalid.')
        ->assertSuccessful();

    expect(Contact::query()->where('email', 'ada@example.test')->exists())->toBeTrue();
});

it('fails for an unknown source', function (): void {
    $this->artisan('nettmail:sync-contacts', ['source' => 'unknown'])
        ->assertFailed();
});
