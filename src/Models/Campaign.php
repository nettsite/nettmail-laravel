<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Nettsite\NettMail\Core\Domain\Campaigns\Campaign as CoreCampaign;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;

class Campaign extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_campaigns';

    protected $fillable = [
        'list_id',
        'segment_id',
        'template_id',
        'sender_id',
        'name',
        'subject',
        'status',
        'track_opens',
        'track_clicks',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /** @return BelongsTo<MailingList, $this> */
    public function list(): BelongsTo
    {
        return $this->belongsTo(MailingList::class, 'list_id');
    }

    /** @return BelongsTo<Segment, $this> */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /** @return BelongsTo<Sender, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class, 'sender_id');
    }

    /** @return HasMany<Send, $this> */
    public function sends(): HasMany
    {
        return $this->hasMany(Send::class, 'campaign_id');
    }

    /** @return HasMany<CampaignLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(CampaignLink::class, 'campaign_id');
    }

    public function toDomain(): CoreCampaign
    {
        return new CoreCampaign(
            id: $this->id,
            name: $this->name,
            subject: $this->subject,
            templateId: $this->template_id,
            listId: $this->list_id,
            segmentId: $this->segment_id,
            senderId: $this->sender_id,
            status: $this->status,
            scheduledAt: $this->scheduled_at?->toDateTimeImmutable(),
        );
    }

    public function fillFromDomain(CoreCampaign $campaign): void
    {
        $this->name = $campaign->name;
        $this->subject = $campaign->subject;
        $this->template_id = $campaign->templateId;
        $this->list_id = $campaign->listId;
        $this->segment_id = $campaign->segmentId;
        $this->sender_id = $campaign->senderId;
        $this->status = $campaign->status;
        $this->scheduled_at = $campaign->scheduledAt !== null ? Carbon::instance($campaign->scheduledAt) : null;
    }
}
