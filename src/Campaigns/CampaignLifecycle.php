<?php

namespace NettSite\NettMail\Campaigns;

use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use NettSite\NettMail\Jobs\ProcessCampaignSend;
use NettSite\NettMail\Jobs\SendCampaignEmail;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\Send;

final class CampaignLifecycle
{
    public function pause(Campaign $campaign): void
    {
        $campaign->transitionTo(CampaignStatus::Paused);
    }

    public function resume(Campaign $campaign): void
    {
        $campaign->transitionTo(CampaignStatus::Sending);

        Send::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->each(fn (Send $send) => SendCampaignEmail::dispatch($send->id));

        ProcessCampaignSend::dispatch($campaign->id);
    }
}
