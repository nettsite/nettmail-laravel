<?php

namespace NettSite\NettMail\Mail;

use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage as CoreEmailMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Converts a Symfony Mime `Email` (built by Laravel's Mailable) into a
 * core `EmailMessage`, the inverse of core's `SymfonyEmailFactory`.
 */
final class SymfonyEmailConverter
{
    private const SKIP_HEADERS = [
        'from', 'to', 'cc', 'bcc', 'reply-to', 'subject',
        'content-type', 'content-transfer-encoding', 'mime-version', 'date', 'message-id',
    ];

    public static function toEmailMessage(Email $email): CoreEmailMessage
    {
        $from = $email->getFrom()[0] ?? null;

        if ($from === null) {
            throw new \InvalidArgumentException('Email must have a From address.');
        }

        $replyTo = $email->getReplyTo()[0] ?? null;

        return new CoreEmailMessage(
            from: self::toAddress($from),
            to: array_map(self::toAddress(...), $email->getTo()),
            subject: $email->getSubject() ?? '',
            html: self::toBodyString($email->getHtmlBody()),
            text: self::toBodyString($email->getTextBody()),
            cc: array_map(self::toAddress(...), $email->getCc()),
            bcc: array_map(self::toAddress(...), $email->getBcc()),
            replyTo: $replyTo !== null ? self::toAddress($replyTo) : null,
            attachments: array_map(self::toAttachment(...), $email->getAttachments()),
            headers: self::toHeaders($email),
        );
    }

    private static function toAddress(Address $address): EmailAddress
    {
        return new EmailAddress($address->getAddress(), $address->getName() ?: null);
    }

    /**
     * @return array{path: string, name: string}
     */
    private static function toAttachment(DataPart $part): array
    {
        $name = $part->getFilename() ?? $part->getName() ?? 'attachment';
        $path = tempnam(sys_get_temp_dir(), 'nettmail_');

        file_put_contents($path, $part->getBody());

        return ['path' => $path, 'name' => $name];
    }

    /**
     * @return array<string, string>
     */
    private static function toHeaders(Email $email): array
    {
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            if (in_array(strtolower($header->getName()), self::SKIP_HEADERS, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }

    private static function toBodyString(mixed $body): ?string
    {
        if ($body === null) {
            return null;
        }

        if (is_string($body)) {
            return $body;
        }

        return stream_get_contents($body) ?: null;
    }
}
