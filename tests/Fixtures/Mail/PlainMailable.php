<?php

namespace NettSite\NettMail\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailable;

class PlainMailable extends Mailable
{
    public function build(): self
    {
        return $this
            ->subject('Plain subject')
            ->html('<html><body><p>Hello</p><a href="https://example.com">Link</a></body></html>');
    }
}
