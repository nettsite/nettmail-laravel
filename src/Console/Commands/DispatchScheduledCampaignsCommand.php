<?php

namespace NettSite\NettMail\Console\Commands;

use Illuminate\Console\Command;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use NettSite\NettMail\Jobs\ProcessCampaignSend;
use NettSite\NettMail\Models\Campaign;

class DispatchScheduledCampaignsCommand extends Command
{
    protected $signature = 'nettmail:dispatch-scheduled';

    protected $description = 'Move due scheduled campaigns to sending and dispatch their sends';

    public function handle(): int
    {
        $campaigns = Campaign::query()
            ->where('status', CampaignStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->transitionTo(CampaignStatus::Sending);

            ProcessCampaignSend::dispatch($campaign->id);
        }

        $this->comment("Dispatched {$campaigns->count()} campaign(s).");

        return self::SUCCESS;
    }
}
