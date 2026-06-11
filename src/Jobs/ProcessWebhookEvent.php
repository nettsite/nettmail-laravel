<?php

namespace NettSite\NettMail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Nettsite\NettMail\Core\Domain\Bounces\BounceClassifier;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact as CoreContact;
use Nettsite\NettMail\Core\Domain\Tracking\EventRecorder;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;
use Nettsite\NettMail\Core\Drivers\Support\MessageIdNormalizer;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;
use NettSite\NettMail\Webhooks\WebhookHandlerResolver;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $eventId,
    ) {}

    public function handle(WebhookHandlerResolver $resolver): void
    {
        $event = Event::query()->find($this->eventId);

        if ($event === null) {
            return;
        }

        $handler = $resolver->resolve($event->provider);

        if ($handler === null) {
            return;
        }

        $normalizedEvents = $handler->parse($event->payload);

        if ($normalizedEvents === []) {
            $event->update(['processed_at' => now()]);

            return;
        }

        $primary = $normalizedEvents[0];
        $send = $this->findSend($primary);

        if ($send !== null) {
            $this->applyToSend($send, $normalizedEvents);
        }

        $event->update([
            'send_id' => $send?->id,
            'type' => $primary->type->value,
            'processed_at' => now(),
        ]);
    }

    /**
     * @param  array<int, NormalizedEvent>  $normalizedEvents
     */
    private function applyToSend(Send $send, array $normalizedEvents): void
    {
        $classifier = new BounceClassifier((int) config('nettmail.bounces.soft_limit'));
        $contact = $send->contact;
        $contactDomain = $contact->toDomain();
        $contactChanged = false;

        foreach ($normalizedEvents as $normalizedEvent) {
            match ($normalizedEvent->type) {
                EventType::Delivered => $this->handleDelivered($send, $contactDomain, $classifier, $normalizedEvent),
                EventType::Opened => $this->handleOpened($send, $normalizedEvent),
                EventType::Clicked => $this->handleClicked($send, $normalizedEvent),
                EventType::HardBounced => $classifier->recordEvent($contactDomain, BounceType::Hard, $normalizedEvent->occurredAt),
                EventType::SoftBounced => $classifier->recordEvent($contactDomain, BounceType::Soft, $normalizedEvent->occurredAt),
                EventType::Complained => $classifier->recordEvent($contactDomain, BounceType::Complaint, $normalizedEvent->occurredAt),
                EventType::Unsubscribed => $contactDomain->globalUnsubscribedAt = $normalizedEvent->occurredAt,
                EventType::Sent => null,
            };

            if (in_array($normalizedEvent->type, [
                EventType::Delivered,
                EventType::HardBounced,
                EventType::SoftBounced,
                EventType::Complained,
                EventType::Unsubscribed,
            ], true)) {
                $contactChanged = true;
            }

            if (in_array($normalizedEvent->type, [EventType::HardBounced, EventType::SoftBounced], true)) {
                $send->bounced_at = Carbon::instance($normalizedEvent->occurredAt);
            }
        }

        $send->save();

        if ($contactChanged) {
            $contact->fillFromDomain($contactDomain);
            $contact->save();
        }
    }

    private function handleDelivered(Send $send, CoreContact $contactDomain, BounceClassifier $classifier, NormalizedEvent $normalizedEvent): void
    {
        $send->delivered_at = Carbon::instance($normalizedEvent->occurredAt);
        $classifier->recordSuccessfulDelivery($contactDomain);
    }

    private function handleOpened(Send $send, NormalizedEvent $normalizedEvent): void
    {
        $existingOpenedAt = $send->opened_at?->toDateTimeImmutable();

        if ((new EventRecorder)->isFirstOpen($existingOpenedAt)) {
            $send->opened_at = Carbon::instance($normalizedEvent->occurredAt);
        }
    }

    private function handleClicked(Send $send, NormalizedEvent $normalizedEvent): void
    {
        if ($send->clicked_at === null) {
            $send->clicked_at = Carbon::instance($normalizedEvent->occurredAt);
        }
    }

    private function findSend(NormalizedEvent $normalizedEvent): ?Send
    {
        $providerMessageId = MessageIdNormalizer::strip($normalizedEvent->providerMessageId);

        if ($providerMessageId === null) {
            return null;
        }

        return Send::query()->where('provider_message_id', $providerMessageId)->first();
    }
}
