<?php

namespace NettSite\NettMail\Contacts;

use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

final class ContactsCsvExporter
{
    public function exportList(MailingList $list): string
    {
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, ['email', 'first_name', 'last_name', 'phone', 'status', 'tags', 'subscribed_at'], escape: '');

        $list->members()
            ->with('contact')
            ->chunk(200, function (iterable $members) use ($stream): void {
                /** @var ListContact $member */
                foreach ($members as $member) {
                    fputcsv($stream, [
                        $member->contact->email,
                        $member->contact->first_name,
                        $member->contact->last_name,
                        $member->contact->phone,
                        $member->status->value,
                        implode(',', $member->tags ?? []),
                        $member->subscribed_at?->format(DATE_ATOM),
                    ], escape: '');
                }
            });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
