<?php

namespace NettSite\NettMail\Contacts;

use DateTimeImmutable;
use Illuminate\Support\Facades\Mail;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Domain\Contacts\OptInTokenGenerator;
use NettSite\NettMail\Mail\OptInConfirmationMail;
use NettSite\NettMail\Models\ListContact;

final class DoubleOptInNotifier
{
    public function __construct(
        private readonly OptInTokenGenerator $tokens,
    ) {}

    public function handle(ListContact $listContact): void
    {
        if ($listContact->status !== MembershipStatus::Pending) {
            return;
        }

        if (! $listContact->wasRecentlyCreated && ! $listContact->wasChanged('status')) {
            return;
        }

        $list = $listContact->list;

        if ($list === null || ! $list->double_optin) {
            return;
        }

        $token = $this->tokens->generate(
            $listContact->contact_id,
            $listContact->list_id,
            new DateTimeImmutable('+24 hours'),
        );

        Mail::to($listContact->contact->email)->send(
            new OptInConfirmationMail($listContact->contact, $list, $token),
        );
    }
}
