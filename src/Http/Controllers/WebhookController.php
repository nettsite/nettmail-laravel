<?php

namespace NettSite\NettMail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use NettSite\NettMail\Jobs\ProcessWebhookEvent;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Webhooks\WebhookEventIdResolver;
use NettSite\NettMail\Webhooks\WebhookHandlerResolver;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookHandlerResolver $resolver,
    ) {}

    public function handle(Request $request, string $provider): Response
    {
        $handler = $this->resolver->resolve($provider);

        if ($handler === null) {
            return response('', 404);
        }

        $rawBody = $request->getContent();
        $headers = $this->lowercasedHeaders($request);
        $secret = (string) config("nettmail.drivers.{$provider}.webhook_secret", '');

        if (! $handler->verify($rawBody, $headers, $secret)) {
            Log::warning("NettMail webhook signature verification failed for provider [{$provider}].");

            return response('', 401);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($rawBody, true) ?? [];

        $eventId = WebhookEventIdResolver::resolve($provider, $payload, $headers, $rawBody);

        $event = Event::query()->firstOrCreate(
            ['provider' => $provider, 'provider_event_id' => $eventId],
            ['type' => 'pending', 'payload' => $payload],
        );

        if (! $event->wasRecentlyCreated) {
            return response('', 200);
        }

        ProcessWebhookEvent::dispatch($event->id);

        return response('', 200);
    }

    /**
     * @return array<string, string>
     */
    private function lowercasedHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $headers[strtolower($name)] = $values[0] ?? '';
        }

        return $headers;
    }
}
