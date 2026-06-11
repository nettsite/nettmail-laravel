<?php

use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\CampaignLink;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

it('serves a tracking pixel and stamps opened_at on first open', function (): void {
    $send = Send::factory()->create(['status' => 'sent']);

    $response = $this->get("/nettmail/track/open/{$send->send_token}");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('image/png');

    $send->refresh();
    expect($send->opened_at)->not->toBeNull();

    expect(Event::query()->where('send_id', $send->id)->where('type', 'opened')->count())->toBe(1);

    $firstOpenedAt = $send->opened_at;

    $this->get("/nettmail/track/open/{$send->send_token}")->assertOk();

    $send->refresh();
    expect($send->opened_at->equalTo($firstOpenedAt))->toBeTrue();
    expect(Event::query()->where('send_id', $send->id)->where('type', 'opened')->count())->toBe(2);
});

it('returns the pixel even for an unknown send token', function (): void {
    $this->get('/nettmail/track/open/unknown-token')->assertOk();
});

it('redirects to the original url and stamps clicked_at', function (): void {
    $send = Send::factory()->create(['status' => 'sent']);
    $send->update(['campaign_id' => Campaign::factory()->create()->id]);

    $link = CampaignLink::factory()->create([
        'campaign_id' => $send->campaign_id,
        'link_hash' => 'abc123',
        'url' => 'https://example.test/landing',
    ]);

    $response = $this->get("/nettmail/track/click/{$send->send_token}/abc123");

    $response->assertRedirect('https://example.test/landing');

    $send->refresh();
    expect($send->clicked_at)->not->toBeNull();
    expect(Event::query()->where('send_id', $send->id)->where('type', 'clicked')->count())->toBe(1);
});

it('returns 404 for an unknown link hash', function (): void {
    $send = Send::factory()->create(['status' => 'sent']);

    $this->get("/nettmail/track/click/{$send->send_token}/unknown-hash")->assertStatus(404);
});

it('returns 404 for an unknown send token on click', function (): void {
    $this->get('/nettmail/track/click/unknown-token/abc123')->assertStatus(404);
});
