<?php

namespace NettSite\NettMail\Mail;

use Illuminate\Mail\Mailable;

class TemplatePreviewMail extends Mailable
{
    public function __construct(
        public readonly string $previewSubject,
        public readonly string $previewHtml,
    ) {}

    public function build(): self
    {
        return $this->subject($this->previewSubject)->html($this->previewHtml);
    }
}
