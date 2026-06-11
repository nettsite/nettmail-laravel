<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Send;
use Orchestra\Testbench\Factories\UserFactory;

it('renders the dashboard with stats and recent sends', function (): void {
    $contact = Contact::factory()->create(['email' => 'ada@example.test']);

    Send::factory()->create([
        'contact_id' => $contact->id,
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    Contact::factory()->create(['bounce_type' => BounceType::Hard]);

    $this->actingAs(UserFactory::new()->create())
        ->get(route('nettmail.dashboard'))
        ->assertSuccessful()
        ->assertSee('ada@example.test')
        ->assertSee('Hard bounces');
});
