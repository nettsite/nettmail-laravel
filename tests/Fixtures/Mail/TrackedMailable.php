<?php

namespace NettSite\NettMail\Tests\Fixtures\Mail;

use NettSite\NettMail\Mail\NettMailMailable;

class TrackedMailable extends NettMailMailable
{
    public function build(): self
    {
        $this->trackOpens();
        $this->trackClicks();
        $this->transactionalKey('welcome-email');

        return $this
            ->subject('Tracked subject')
            ->html('<html><body><p>Hello</p><a href="https://example.com">Link</a></body></html>');
    }
}
