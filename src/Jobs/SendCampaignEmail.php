<?php

namespace NettSite\NettMail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignSender;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use Nettsite\NettMail\Core\Domain\Campaigns\PreparedCampaignTemplate;
use Nettsite\NettMail\Core\Domain\Campaigns\UnsubscribeHeaders;
use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeTokenGenerator;
use Nettsite\NettMail\Core\Drivers\Support\MessageIdNormalizer;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\NettMail as CoreNettMail;
use NettSite\NettMail\Models\Send;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $sendId,
    ) {}

    /**
     * @return array<int, RateLimited>
     */
    public function middleware(): array
    {
        return [new RateLimited('nettmail-campaign-send')];
    }

    public function handle(CoreNettMail $nettMail, UnsubscribeTokenGenerator $unsubscribeTokens): void
    {
        $send = Send::query()->find($this->sendId);

        if ($send === null || $send->status !== 'queued') {
            return;
        }

        $campaign = $send->campaign;

        if ($campaign === null || $campaign->status !== CampaignStatus::Sending) {
            return;
        }

        $contact = $send->contact;

        if ($contact->toDomain()->isSuppressed()) {
            $send->status = 'suppressed';
            $send->save();

            return;
        }

        $unsubscribeUrl = rtrim((string) config('app.url'), '/')
            .'/'.config('nettmail.routes.prefix')
            .'/unsubscribe/'.$unsubscribeTokens->generate($contact->id, $campaign->list_id);

        $rendered = (new CampaignSender)->renderForContact(
            $campaign->subject,
            new PreparedCampaignTemplate(
                (string) $campaign->prepared_html,
                (string) $campaign->prepared_text,
                [],
                (string) $campaign->send_token_placeholder,
            ),
            $send->send_token,
            [
                'first_name' => (string) $contact->first_name,
                'last_name' => (string) $contact->last_name,
                'email' => $contact->email,
                'unsubscribe_url' => $unsubscribeUrl,
            ],
        );

        $headers = (new UnsubscribeHeaders)->build($unsubscribeUrl);

        $sender = $campaign->sender;
        $from = new EmailAddress(
            $sender === null ? (string) config('nettmail.from.email') : $sender->from_email,
            $sender === null ? config('nettmail.from.name') : $sender->from_name,
        );

        $recipientName = trim((string) $contact->first_name.' '.(string) $contact->last_name);

        $result = $nettMail->send(new EmailMessage(
            from: $from,
            to: [new EmailAddress($contact->email, $recipientName !== '' ? $recipientName : null)],
            subject: $rendered['subject'],
            html: $rendered['html'],
            text: $rendered['text'],
            headers: $headers,
        ));

        $send->status = $result->success ? 'sent' : 'failed';
        $send->sent_at = $result->success ? now() : null;
        $send->provider_message_id = MessageIdNormalizer::strip($result->messageId);
        $send->save();
    }
}
