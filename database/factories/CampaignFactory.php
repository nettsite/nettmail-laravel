<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\MailingList;
use NettSite\NettMail\Models\Template;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'list_id' => MailingList::factory(),
            'template_id' => Template::factory(),
            'name' => $this->faker->words(3, true),
            'subject' => $this->faker->sentence(),
            'status' => CampaignStatus::Draft,
            'track_opens' => true,
            'track_clicks' => true,
        ];
    }
}
