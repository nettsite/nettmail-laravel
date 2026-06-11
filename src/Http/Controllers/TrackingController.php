<?php

namespace NettSite\NettMail\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Nettsite\NettMail\Core\Domain\Tracking\EventRecorder;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use NettSite\NettMail\Models\CampaignLink;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

final class TrackingController extends Controller
{
    /**
     * 1x1 transparent PNG.
     */
    private const PIXEL = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";

    public function open(string $sendToken): Response
    {
        $send = Send::query()->where('send_token', $sendToken)->first();

        if ($send !== null) {
            if ((new EventRecorder)->isFirstOpen($send->opened_at?->toDateTimeImmutable())) {
                $send->opened_at = now();
                $send->save();
            }

            Event::query()->create([
                'send_id' => $send->id,
                'type' => EventType::Opened->value,
                'provider' => 'nettmail',
                'payload' => [],
                'processed_at' => now(),
            ]);
        }

        return response(self::PIXEL, 200)->header('Content-Type', 'image/png');
    }

    public function click(string $sendToken, string $linkHash): RedirectResponse|Response
    {
        $send = Send::query()->where('send_token', $sendToken)->first();

        if ($send === null) {
            return response('', 404);
        }

        $link = CampaignLink::query()
            ->where('campaign_id', $send->campaign_id)
            ->where('link_hash', $linkHash)
            ->first();

        if ($link === null) {
            return response('', 404);
        }

        if ($send->clicked_at === null) {
            $send->clicked_at = now();
            $send->save();
        }

        Event::query()->create([
            'send_id' => $send->id,
            'type' => EventType::Clicked->value,
            'provider' => 'nettmail',
            'payload' => ['link_hash' => $linkHash, 'url' => $link->url],
            'processed_at' => now(),
        ]);

        return redirect()->away($link->url);
    }
}
