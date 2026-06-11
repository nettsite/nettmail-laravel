<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nettsite\NettMail\Core\Domain\Contacts\ListMembership as CoreListMembership;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;

class ListContact extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_list_contacts';

    protected $fillable = [
        'list_id',
        'contact_id',
        'status',
        'tags',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'status' => MembershipStatus::class,
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /** @return BelongsTo<MailingList, $this> */
    public function list(): BelongsTo
    {
        return $this->belongsTo(MailingList::class, 'list_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function toDomain(): CoreListMembership
    {
        return new CoreListMembership(
            contactId: $this->contact_id,
            listId: $this->list_id,
            status: $this->status,
            tags: $this->tags ?? [],
            subscribedAt: $this->subscribed_at?->toDateTimeImmutable(),
        );
    }

    public function fillFromDomain(CoreListMembership $membership): void
    {
        $this->list_id = $membership->listId;
        $this->contact_id = $membership->contactId;
        $this->status = $membership->status;
        $this->tags = $membership->tags;
        $this->subscribed_at = $membership->subscribedAt;
    }
}
