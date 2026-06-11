<?php

namespace NettSite\NettMail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

class PurgeRetentionCommand extends Command
{
    protected $signature = 'nettmail:purge';

    protected $description = 'Delete send and event records older than the configured retention period';

    public function handle(): int
    {
        $years = (int) config('nettmail.retention.send_log_years');
        $cutoff = Carbon::now()->subYears($years);

        $sendIds = Send::query()
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        $events = Event::query()->whereIn('send_id', $sendIds)->delete();

        $events += Event::query()
            ->whereNull('send_id')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $sends = Send::query()->whereIn('id', $sendIds)->delete();

        $this->comment("Purged {$sends} send(s) and {$events} event(s) older than {$years} year(s).");

        return self::SUCCESS;
    }
}
