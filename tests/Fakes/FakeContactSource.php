<?php

namespace NettSite\NettMail\Tests\Fakes;

use Nettsite\NettMail\Core\Contracts\ContactSourceContract;

final class FakeContactSource implements ContactSourceContract
{
    /**
     * @param  array<int, array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>, source_id?: string|int}>  $contacts
     */
    public function __construct(
        private readonly array $contacts = [],
    ) {}

    public function label(): string
    {
        return 'Fake Source';
    }

    public function key(): string
    {
        return 'fake';
    }

    public function contacts(): iterable
    {
        return $this->contacts;
    }

    public function findContact(string|int $sourceId): ?array
    {
        foreach ($this->contacts as $contact) {
            if (($contact['source_id'] ?? null) === $sourceId) {
                return $contact;
            }
        }

        return null;
    }
}
