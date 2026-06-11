<?php

use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Send;
use NettSite\NettMail\Models\Template;

it('renders the campaign html with merge tags resolved', function (): void {
    $template = Template::factory()->create([
        'html' => '<p>Hi {{first_name}}, <a href="{{unsubscribe_url}}">unsubscribe</a></p>',
    ]);
    $campaign = Campaign::factory()->create(['template_id' => $template->id]);
    $contact = Contact::factory()->create(['first_name' => 'Ada']);
    $send = Send::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
    ]);

    $response = $this->get("/nettmail/web-version/{$send->send_token}");

    $response->assertOk();
    $response->assertSee('Hi Ada,', false);
    $response->assertSee('/nettmail/unsubscribe/', false);
});

it('returns 404 for a transactional send with no campaign', function (): void {
    $send = Send::factory()->create(['status' => 'sent']);

    $this->get("/nettmail/web-version/{$send->send_token}")->assertStatus(404);
});

it('returns 404 for an unknown send token', function (): void {
    $this->get('/nettmail/web-version/unknown-token')->assertStatus(404);
});
