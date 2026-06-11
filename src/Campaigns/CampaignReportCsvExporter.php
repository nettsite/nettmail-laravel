<?php

namespace NettSite\NettMail\Campaigns;

use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\Send;

final class CampaignReportCsvExporter
{
    public function export(Campaign $campaign): string
    {
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, ['email', 'status', 'sent_at', 'delivered_at', 'opened_at', 'clicked_at', 'bounced_at'], escape: '');

        $campaign->sends()
            ->with('contact')
            ->chunk(200, function (iterable $sends) use ($stream): void {
                /** @var Send $send */
                foreach ($sends as $send) {
                    fputcsv($stream, [
                        $send->contact?->email,
                        $send->status,
                        $send->sent_at?->format(DATE_ATOM),
                        $send->delivered_at?->format(DATE_ATOM),
                        $send->opened_at?->format(DATE_ATOM),
                        $send->clicked_at?->format(DATE_ATOM),
                        $send->bounced_at?->format(DATE_ATOM),
                    ], escape: '');
                }
            });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
