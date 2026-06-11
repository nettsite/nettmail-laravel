<?php

namespace NettSite\NettMail\Commands;

use Illuminate\Console\Command;

class NettMailCommand extends Command
{
    public $signature = 'nettmail';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
