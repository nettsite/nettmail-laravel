<?php

namespace NettSite\NettMail\Mail;

use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;

abstract class NettMailMailable extends Mailable
{
    public const TRACK_OPENS_HEADER = 'X-NettMail-Track-Opens';

    public const TRACK_CLICKS_HEADER = 'X-NettMail-Track-Clicks';

    public const TRANSACTIONAL_KEY_HEADER = 'X-NettMail-Transactional-Key';

    /** @var array<int, string> */
    public const INTERNAL_HEADERS = [
        self::TRACK_OPENS_HEADER,
        self::TRACK_CLICKS_HEADER,
        self::TRANSACTIONAL_KEY_HEADER,
    ];

    /**
     * Insert an open-tracking pixel before `</body>` for this send.
     */
    protected function trackOpens(): static
    {
        return $this->withSymfonyMessage(
            fn (Email $message) => $message->getHeaders()->addTextHeader(self::TRACK_OPENS_HEADER, '1'),
        );
    }

    /**
     * Rewrite links in the HTML body through the click-tracking redirect for this send.
     */
    protected function trackClicks(): static
    {
        return $this->withSymfonyMessage(
            fn (Email $message) => $message->getHeaders()->addTextHeader(self::TRACK_CLICKS_HEADER, '1'),
        );
    }

    /**
     * Tag this send with a transactional key for reporting/lookup in `nettmail_sends`.
     */
    protected function transactionalKey(string $key): static
    {
        return $this->withSymfonyMessage(
            fn (Email $message) => $message->getHeaders()->addTextHeader(self::TRANSACTIONAL_KEY_HEADER, $key),
        );
    }
}
