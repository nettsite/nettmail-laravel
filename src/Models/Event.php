<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_events';

    protected $fillable = [
        'send_id',
        'type',
        'provider',
        'provider_event_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /** @return BelongsTo<Send, $this> */
    public function send(): BelongsTo
    {
        return $this->belongsTo(Send::class, 'send_id');
    }
}
