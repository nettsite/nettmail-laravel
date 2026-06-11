<?php

namespace NettSite\NettMail\Console\Commands;

use Illuminate\Console\Command;
use NettSite\NettMail\Contacts\ContactSourceRegistry;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\ContactSynchronizer;

class SyncContactsCommand extends Command
{
    protected $signature = 'nettmail:sync-contacts {source}';

    protected $description = 'Sync contacts from a registered NettMail contact source';

    public function handle(ContactSourceRegistry $registry, StorageAdapterContract $storage): int
    {
        $key = (string) $this->argument('source');
        $source = $registry->get($key);

        if ($source === null) {
            $this->error("Unknown contact source [{$key}].");

            return self::FAILURE;
        }

        $this->info("Syncing contacts from {$source->label()}...");

        $result = (new ContactSynchronizer($source, $storage))->syncAll();

        $this->comment("Created {$result->created}, updated {$result->updated}, skipped {$result->skippedInvalid} invalid.");

        return self::SUCCESS;
    }
}
