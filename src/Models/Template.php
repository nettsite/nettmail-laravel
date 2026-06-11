<?php

namespace NettSite\NettMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nettsite\NettMail\Core\Domain\Templates\TemplateType;

class Template extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'nettmail_templates';

    protected $fillable = [
        'name',
        'type',
        'subject',
        'design',
        'html',
        'plain_text',
        'archived_at',
    ];

    protected $casts = [
        'type' => TemplateType::class,
        'design' => 'array',
        'archived_at' => 'datetime',
    ];

    /** @return HasMany<Campaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }
}
