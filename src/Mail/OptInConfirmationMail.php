<?php

namespace NettSite\NettMail\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\MailingList;

class OptInConfirmationMail extends NettMailMailable
{
    public function __construct(
        public readonly Contact $contact,
        public readonly MailingList $list,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Please confirm your subscription to {$this->list->name}");
    }

    public function content(): Content
    {
        $confirmUrl = rtrim((string) config('app.url'), '/')
            .'/'.config('nettmail.routes.prefix')
            .'/opt-in/'.$this->token;

        return new Content(
            view: 'nettmail::mail.opt-in-confirmation',
            with: [
                'confirmUrl' => $confirmUrl,
                'contact' => $this->contact,
                'list' => $this->list,
            ],
        );
    }
}
