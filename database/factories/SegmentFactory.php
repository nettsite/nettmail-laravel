<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\NettMail\Models\MailingList;
use NettSite\NettMail\Models\Segment;

/** @extends Factory<Segment> */
class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        return [
            'list_id' => MailingList::factory(),
            'name' => $this->faker->words(2, true),
            'conditions' => [],
        ];
    }
}
