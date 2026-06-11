<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Send;

/** @extends Factory<Send> */
class SendFactory extends Factory
{
    protected $model = Send::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'send_token' => $this->faker->unique()->uuid(),
            'status' => 'queued',
        ];
    }
}
