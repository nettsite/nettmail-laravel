<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList as CoreMailingList;

class MailingList extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_lists';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sender_id',
        'double_optin',
        'contact_source_key',
    ];

    protected $casts = [
        'double_optin' => 'boolean',
    ];

    /** @return BelongsTo<Sender, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class, 'sender_id');
    }

    /** @return HasMany<ListContact, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ListContact::class, 'list_id');
    }

    /** @return HasMany<Segment, $this> */
    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class, 'list_id');
    }

    /** @return HasMany<Campaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'list_id');
    }

    public function toDomain(): CoreMailingList
    {
        return new CoreMailingList(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            doubleOptin: $this->double_optin,
            senderId: $this->sender_id,
        );
    }

    public function fillFromDomain(CoreMailingList $list): void
    {
        $this->name = $list->name;
        $this->slug = $list->slug;
        $this->description = $list->description;
        $this->sender_id = $list->senderId;
        $this->double_optin = $list->doubleOptin;
    }
}
