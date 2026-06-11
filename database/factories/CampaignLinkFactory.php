<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\CampaignLink;

/** @extends Factory<CampaignLink> */
class CampaignLinkFactory extends Factory
{
    protected $model = CampaignLink::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'link_hash' => $this->faker->unique()->md5(),
            'url' => $this->faker->url(),
        ];
    }
}
