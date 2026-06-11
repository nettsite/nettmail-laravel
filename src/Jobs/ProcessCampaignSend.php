<?php

namespace NettSite\NettMail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use NettSite\NettMail\Campaigns\CampaignAudienceResolver;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignTemplatePreparer;
use Nettsite\NettMail\Core\Domain\Templates\MissingUnsubscribeLinkException;
use Nettsite\NettMail\Core\Domain\Templates\TemplateCompiler;
use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;
use Nettsite\NettMail\Core\Domain\Tracking\PixelGenerator;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\CampaignLink;
use NettSite\NettMail\Models\Send;

class ProcessCampaignSend implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $campaignId,
    ) {}

    public function handle(CampaignAudienceResolver $audience): void
    {
        $campaign = Campaign::query()->find($this->campaignId);

        if ($campaign === null || $campaign->status !== CampaignStatus::Sending) {
            return;
        }

        try {
            $this->prepareTemplate($campaign);
        } catch (MissingUnsubscribeLinkException) {
            $campaign->transitionTo(CampaignStatus::Failed);

            return;
        }

        foreach ($audience->resolve($campaign) as $membership) {
            $send = Send::query()->firstOrCreate(
                ['campaign_id' => $campaign->id, 'contact_id' => $membership->contact_id],
                ['send_token' => Str::random(40), 'status' => 'queued'],
            );

            if ($send->wasRecentlyCreated) {
                SendCampaignEmail::dispatch($send->id);
            }
        }

        $this->markSentIfComplete($campaign);
    }

    private function prepareTemplate(Campaign $campaign): void
    {
        if ($campaign->prepared_html !== null) {
            return;
        }

        $template = $campaign->template;

        $compiled = (new TemplateCompiler)->compile((string) $template->html, $template->type);

        $baseUrl = (string) config('app.url');
        $preparer = new CampaignTemplatePreparer(new LinkRewriter($baseUrl), new PixelGenerator($baseUrl));

        $prepared = $preparer->prepare(
            $compiled,
            config('nettmail.compliance.physical_address'),
            $campaign->track_clicks,
            $campaign->track_opens,
        );

        foreach ($prepared->links as $hash => $url) {
            CampaignLink::query()->firstOrCreate(
                ['campaign_id' => $campaign->id, 'link_hash' => $hash],
                ['url' => $url],
            );
        }

        $campaign->prepared_html = $prepared->html;
        $campaign->prepared_text = $prepared->text;
        $campaign->send_token_placeholder = $prepared->sendTokenPlaceholder;
        $campaign->save();
    }

    private function markSentIfComplete(Campaign $campaign): void
    {
        $hasUnsentRows = Send::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->exists();

        if ($hasUnsentRows) {
            return;
        }

        $campaign->transitionTo(CampaignStatus::Sent);
        $campaign->sent_at = now();
        $campaign->save();
    }
}
