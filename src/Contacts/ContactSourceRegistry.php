<?php

namespace NettSite\NettMail\Contacts;

use Nettsite\NettMail\Core\Contracts\ContactSourceContract;

final class ContactSourceRegistry
{
    /** @var array<string, ContactSourceContract> */
    private array $sources = [];

    public function register(ContactSourceContract $source): void
    {
        $this->sources[$source->key()] = $source;
    }

    public function get(string $key): ?ContactSourceContract
    {
        return $this->sources[$key] ?? null;
    }

    /** @return array<string, ContactSourceContract> */
    public function all(): array
    {
        return $this->sources;
    }
}
