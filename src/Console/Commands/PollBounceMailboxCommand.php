<?php

namespace NettSite\NettMail\Console\Commands;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Nettsite\NettMail\Core\Contracts\BounceParserContract;
use Nettsite\NettMail\Core\Contracts\MailboxContract;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Bounces\BounceClassifier;
use Nettsite\NettMail\Core\Domain\Bounces\BouncePoller;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Send;

class PollBounceMailboxCommand extends Command
{
    protected $signature = 'nettmail:poll-bounces';

    protected $description = 'Poll the bounce mailbox for DSN messages and apply suppression rules';

    public function handle(MailboxContract $mailbox, BounceParserContract $parser, StorageAdapterContract $storage): int
    {
        $classifier = new BounceClassifier((int) config('nettmail.bounces.soft_limit'));

        $poller = new BouncePoller(
            $mailbox,
            $parser,
            $classifier,
            $storage,
            (string) config('nettmail.bounces.mailbox.processed_folder'),
            (string) config('nettmail.bounces.mailbox.unrecognised_folder'),
        );

        $result = $poller->poll();

        $reset = $this->resetStaleSoftBounces($classifier);

        $this->comment("Processed {$result->processed} bounce(s), {$result->unrecognised} unrecognised, {$reset} soft bounce counter(s) reset.");

        return self::SUCCESS;
    }

    private function resetStaleSoftBounces(BounceClassifier $classifier): int
    {
        $resetDays = (int) config('nettmail.bounces.soft_reset_days');
        $now = new DateTimeImmutable;
        $reset = 0;

        Contact::query()
            ->where('bounce_type', BounceType::Soft)
            ->each(function (Contact $contact) use ($classifier, $resetDays, $now, &$reset): void {
                $lastSentAt = Send::query()
                    ->where('contact_id', $contact->id)
                    ->where('status', 'sent')
                    ->max('sent_at');

                if ($lastSentAt === null) {
                    return;
                }

                $domain = $contact->toDomain();

                if (! $classifier->resetStaleSoftBounces($domain, Carbon::parse($lastSentAt)->toDateTimeImmutable(), $now, $resetDays)) {
                    return;
                }

                $contact->fillFromDomain($domain);
                $contact->save();

                $reset++;
            });

        return $reset;
    }
}
