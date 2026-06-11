<?php

namespace NettSite\NettMail\Webhooks;

/**
 * Derives a per-provider unique id for a webhook delivery, used as
 * `provider_event_id` for `(provider, provider_event_id)` dedupe.
 */
final class WebhookEventIdResolver
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public static function resolve(string $provider, array $payload, array $headers, string $rawBody): string
    {
        return match ($provider) {
            'resend' => $headers['svix-id'] ?? hash('sha256', $rawBody),
            'mailgun' => $payload['signature']['token'] ?? hash('sha256', $rawBody),
            'ses' => $payload['MessageId'] ?? hash('sha256', $rawBody),
            default => hash('sha256', $rawBody),
        };
    }
}
