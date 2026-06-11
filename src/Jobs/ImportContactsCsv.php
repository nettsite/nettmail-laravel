<?php

namespace NettSite\NettMail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\CsvContactImporter;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;

class ImportContactsCsv implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, string>  $columnMap
     * @param  array<int, string>  $tags
     */
    public function __construct(
        private readonly string $csv,
        private readonly array $columnMap,
        private readonly string $listId,
        private readonly array $tags = [],
        private readonly MembershipStatus $initialStatus = MembershipStatus::Subscribed,
    ) {}

    public function handle(StorageAdapterContract $storage): void
    {
        $result = (new CsvContactImporter($storage))->import(
            $this->csv,
            $this->columnMap,
            $this->listId,
            $this->tags,
            $this->initialStatus,
        );

        Log::info('NettMail CSV contact import completed', [
            'list_id' => $this->listId,
            'created' => $result->created,
            'updated' => $result->updated,
            'invalid' => $result->invalid,
            'errors' => $result->errors,
        ]);
    }
}
