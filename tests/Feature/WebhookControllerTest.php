<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

function resendSignature(string $secret, string $id, string $timestamp, string $body): string
{
    $secretBytes = base64_decode(preg_replace('/^whsec_/', '', $secret));

    return 'v1,'.base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $secretBytes, true));
}

beforeEach(function (): void {
    $this->secret = 'whsec_'.base64_encode('test-secret');
    config()->set('nettmail.drivers.resend.webhook_secret', $this->secret);
});

it('returns 404 for an unknown provider', function (): void {
    $this->postJson('/nettmail/webhooks/unknown', [])->assertStatus(404);
});

it('returns 401 when the signature is invalid', function (): void {
    $body = json_encode(['type' => 'email.delivered']);

    $this->call('POST', '/nettmail/webhooks/resend', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_svix-id' => 'msg_123',
        'HTTP_svix-timestamp' => (string) time(),
        'HTTP_svix-signature' => 'v1,invalidsignature==',
    ], $body)->assertStatus(401);
});

it('processes a delivered event and stamps the send', function (): void {
    $contact = Contact::factory()->create(['email' => 'recipient@example.test']);
    $send = Send::factory()->create([
        'contact_id' => $contact->id,
        'provider_message_id' => 'abc-123',
        'status' => 'sent',
    ]);

    $body = json_encode([
        'type' => 'email.delivered',
        'created_at' => '2024-01-01T00:00:00Z',
        'data' => ['email_id' => 'abc-123'],
    ]);
    $timestamp = (string) time();
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_svix-id' => 'msg_123',
        'HTTP_svix-timestamp' => $timestamp,
        'HTTP_svix-signature' => resendSignature($this->secret, 'msg_123', $timestamp, $body),
    ];

    $this->call('POST', '/nettmail/webhooks/resend', [], [], [], $headers, $body)->assertStatus(200);

    $send->refresh();
    expect($send->delivered_at)->not->toBeNull();

    $event = Event::query()->where('provider', 'resend')->where('provider_event_id', 'msg_123')->first();
    expect($event)->not->toBeNull();
    expect($event->type)->toBe('delivered');
    expect($event->send_id)->toBe($send->id);
    expect($event->processed_at)->not->toBeNull();
});

it('escalates a contact to hard bounce after the soft bounce threshold', function (): void {
    config()->set('nettmail.bounces.soft_limit', 2);

    $contact = Contact::factory()->create([
        'email' => 'bouncer@example.test',
        'consecutive_soft_bounces' => 1,
    ]);
    $send = Send::factory()->create([
        'contact_id' => $contact->id,
        'provider_message_id' => 'soft-456',
        'status' => 'sent',
    ]);

    $body = json_encode([
        'type' => 'email.bounced',
        'created_at' => '2024-01-01T00:00:00Z',
        'data' => ['email_id' => 'soft-456'],
    ]);
    $timestamp = (string) time();
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_svix-id' => 'msg_456',
        'HTTP_svix-timestamp' => $timestamp,
        'HTTP_svix-signature' => resendSignature($this->secret, 'msg_456', $timestamp, $body),
    ];

    $this->call('POST', '/nettmail/webhooks/resend', [], [], [], $headers, $body)->assertStatus(200);

    $contact->refresh();
    $send->refresh();

    expect($contact->bounce_type)->toBe(BounceType::Hard);
    expect($send->bounced_at)->not->toBeNull();
});

it('does not reprocess a duplicate webhook delivery', function (): void {
    $contact = Contact::factory()->create(['email' => 'dup@example.test']);
    $send = Send::factory()->create([
        'contact_id' => $contact->id,
        'provider_message_id' => 'dup-789',
        'status' => 'sent',
    ]);

    $body = json_encode([
        'type' => 'email.delivered',
        'created_at' => '2024-01-01T00:00:00Z',
        'data' => ['email_id' => 'dup-789'],
    ]);
    $timestamp = (string) time();
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_svix-id' => 'msg_789',
        'HTTP_svix-timestamp' => $timestamp,
        'HTTP_svix-signature' => resendSignature($this->secret, 'msg_789', $timestamp, $body),
    ];

    $this->call('POST', '/nettmail/webhooks/resend', [], [], [], $headers, $body)->assertStatus(200);
    $this->call('POST', '/nettmail/webhooks/resend', [], [], [], $headers, $body)->assertStatus(200);

    expect(Event::query()->where('provider', 'resend')->where('provider_event_id', 'msg_789')->count())->toBe(1);
});
