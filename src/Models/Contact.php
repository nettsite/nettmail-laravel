<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact as CoreContact;

class Contact extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_contacts';

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'metadata',
        'source_type',
        'source_id',
        'global_unsubscribed_at',
        'bounced_at',
        'bounce_type',
        'consecutive_soft_bounces',
    ];

    protected $casts = [
        'metadata' => 'array',
        'global_unsubscribed_at' => 'datetime',
        'bounced_at' => 'datetime',
        'bounce_type' => BounceType::class,
        'consecutive_soft_bounces' => 'integer',
    ];

    /** @return HasMany<ListContact, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(ListContact::class, 'contact_id');
    }

    /** @return HasMany<Send, $this> */
    public function sends(): HasMany
    {
        return $this->hasMany(Send::class, 'contact_id');
    }

    public function toDomain(): CoreContact
    {
        return new CoreContact(
            id: $this->id,
            email: $this->email,
            firstName: $this->first_name,
            lastName: $this->last_name,
            phone: $this->phone,
            metadata: $this->metadata ?? [],
            sourceType: $this->source_type,
            sourceId: $this->source_id,
            globalUnsubscribedAt: $this->global_unsubscribed_at?->toDateTimeImmutable(),
            bouncedAt: $this->bounced_at?->toDateTimeImmutable(),
            bounceType: $this->bounce_type,
            consecutiveSoftBounces: $this->consecutive_soft_bounces,
        );
    }

    public function fillFromDomain(CoreContact $contact): void
    {
        $this->email = $contact->email;
        $this->first_name = $contact->firstName;
        $this->last_name = $contact->lastName;
        $this->phone = $contact->phone;
        $this->metadata = $contact->metadata;
        $this->source_type = $contact->sourceType;
        $this->source_id = $contact->sourceId;
        $this->global_unsubscribed_at = $contact->globalUnsubscribedAt;
        $this->bounced_at = $contact->bouncedAt;
        $this->bounce_type = $contact->bounceType;
        $this->consecutive_soft_bounces = $contact->consecutiveSoftBounces;
    }
}
