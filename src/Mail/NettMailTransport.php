<?php

namespace NettSite\NettMail\Mail;

use Illuminate\Support\Str;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\Contact as CoreContact;
use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;
use Nettsite\NettMail\Core\Domain\Tracking\PixelGenerator;
use Nettsite\NettMail\Core\Drivers\Support\MessageIdNormalizer;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage as CoreEmailMessage;
use Nettsite\NettMail\Core\NettMail as CoreNettMail;
use NettSite\NettMail\Models\Send;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Bridges Laravel's mailer to the core driver: every send is logged to
 * `nettmail_sends`, recipients are upserted into `nettmail_contacts`, and
 * suppressed contacts (per `Contact::isSuppressed(isOperationalTransactional: true)`)
 * are skipped without contacting the provider.
 */
final class NettMailTransport extends AbstractTransport
{
    public function __construct(
        private readonly CoreNettMail $nettMail,
        private readonly StorageAdapterContract $storage,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'nettmail';
    }

    protected function doSend(SentMessage $message): void
    {
        $emailMessage = SymfonyEmailConverter::toEmailMessage($this->resolveEmail($message));

        $transactionalKey = $emailMessage->headers[NettMailMailable::TRANSACTIONAL_KEY_HEADER] ?? null;
        $trackOpens = isset($emailMessage->headers[NettMailMailable::TRACK_OPENS_HEADER]);
        $trackClicks = isset($emailMessage->headers[NettMailMailable::TRACK_CLICKS_HEADER]);

        $headers = array_diff_key($emailMessage->headers, array_flip(NettMailMailable::INTERNAL_HEADERS));

        $allowedTo = [];
        $sends = [];

        foreach ($emailMessage->to as $recipient) {
            $contact = $this->upsertContact($recipient);

            $send = new Send([
                'transactional_key' => $transactionalKey,
                'contact_id' => $contact->id,
                'send_token' => Str::random(40),
                'status' => 'queued',
            ]);

            if ($contact->isSuppressed(isOperationalTransactional: true)) {
                $send->status = 'suppressed';
                $send->save();

                continue;
            }

            $allowedTo[] = $recipient;
            $sends[] = $send;
        }

        if ($allowedTo === []) {
            return;
        }

        [$html, $text] = $this->applyTracking($emailMessage->html, $emailMessage->text, $sends[0]->send_token, $trackOpens, $trackClicks);

        $result = $this->nettMail->send(new CoreEmailMessage(
            from: $emailMessage->from,
            to: $allowedTo,
            subject: $emailMessage->subject,
            html: $html,
            text: $text,
            cc: $emailMessage->cc,
            bcc: $emailMessage->bcc,
            replyTo: $emailMessage->replyTo,
            attachments: $emailMessage->attachments,
            headers: $headers,
        ));

        foreach ($sends as $send) {
            $send->status = $result->success ? 'sent' : 'failed';
            $send->sent_at = $result->success ? now() : null;
            $send->provider_message_id = MessageIdNormalizer::strip($result->messageId);
            $send->save();
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function applyTracking(?string $html, ?string $text, string $sendToken, bool $trackOpens, bool $trackClicks): array
    {
        if ($html === null || (! $trackOpens && ! $trackClicks)) {
            return [$html, $text];
        }

        $baseUrl = (string) config('app.url');

        if ($trackClicks) {
            $html = (new LinkRewriter($baseUrl))->rewrite($html, $sendToken)->html;
        }

        if ($trackOpens) {
            $html = (new PixelGenerator($baseUrl))->appendToHtml($html, $sendToken);
        }

        return [$html, $text];
    }

    private function resolveEmail(SentMessage $message): Email
    {
        $original = $message->getOriginalMessage();

        return match (true) {
            $original instanceof Email => $original,
            $original instanceof Message => MessageConverter::toEmail($original),
            default => throw new RuntimeException('NettMail transport only supports Symfony Mime messages.'),
        };
    }

    private function upsertContact(EmailAddress $address): CoreContact
    {
        $contact = $this->storage->findContactByEmail($address->email);

        if ($contact !== null) {
            return $contact;
        }

        return $this->storage->saveContact(new CoreContact(id: null, email: $address->email));
    }
}
