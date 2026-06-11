<?php

namespace NettSite\NettMail\Storage;

use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\Contact as CoreContact;
use Nettsite\NettMail\Core\Domain\Contacts\EmailNormalizer;
use Nettsite\NettMail\Core\Domain\Contacts\ListMembership as CoreListMembership;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList as CoreMailingList;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

class EloquentAdapter implements StorageAdapterContract
{
    public function findContactByEmail(string $email): ?CoreContact
    {
        $model = Contact::query()
            ->where('email', EmailNormalizer::normalize($email))
            ->first();

        return $model?->toDomain();
    }

    public function findContactById(string $id): ?CoreContact
    {
        return Contact::query()->find($id)?->toDomain();
    }

    public function saveContact(CoreContact $contact): CoreContact
    {
        $model = $contact->id !== null
            ? Contact::query()->findOrFail($contact->id)
            : Contact::query()->where('email', $contact->email)->first() ?? new Contact;

        $model->fillFromDomain($contact);
        $model->save();

        return $model->toDomain();
    }

    public function findListById(string $id): ?CoreMailingList
    {
        return MailingList::query()->find($id)?->toDomain();
    }

    public function findListBySlug(string $slug): ?CoreMailingList
    {
        return MailingList::query()->where('slug', $slug)->first()?->toDomain();
    }

    public function saveList(CoreMailingList $list): CoreMailingList
    {
        $model = $list->id !== null
            ? MailingList::query()->findOrFail($list->id)
            : new MailingList;

        $model->fillFromDomain($list);
        $model->save();

        return $model->toDomain();
    }

    public function findMembership(string $contactId, string $listId): ?CoreListMembership
    {
        return ListContact::query()
            ->where('contact_id', $contactId)
            ->where('list_id', $listId)
            ->first()?->toDomain();
    }

    public function saveMembership(CoreListMembership $membership): CoreListMembership
    {
        $model = ListContact::query()
            ->where('contact_id', $membership->contactId)
            ->where('list_id', $membership->listId)
            ->first() ?? new ListContact;

        $model->fillFromDomain($membership);
        $model->save();

        return $model->toDomain();
    }

    public function findSuppressedContacts(): array
    {
        return Contact::query()
            ->whereNotNull('global_unsubscribed_at')
            ->orWhereIn('bounce_type', ['hard', 'complaint'])
            ->get()
            ->map(fn (Contact $contact): CoreContact => $contact->toDomain())
            ->all();
    }
}
