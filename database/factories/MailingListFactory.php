<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\NettMail\Models\MailingList;

/** @extends Factory<MailingList> */
class MailingListFactory extends Factory
{
    protected $model = MailingList::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'double_optin' => false,
        ];
    }
}
