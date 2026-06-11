<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sender extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_senders';

    protected $fillable = [
        'name',
        'from_email',
        'from_name',
        'driver',
        'config',
        'bounce_mailbox',
    ];

    protected $casts = [
        'config' => 'array',
        'bounce_mailbox' => 'array',
    ];

    /** @return HasMany<MailingList, $this> */
    public function lists(): HasMany
    {
        return $this->hasMany(MailingList::class, 'sender_id');
    }

    /** @return HasMany<Campaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'sender_id');
    }
}
