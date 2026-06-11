<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'send_id' => Send::factory(),
            'type' => 'delivered',
            'provider' => 'php',
            'provider_event_id' => $this->faker->unique()->uuid(),
            'payload' => [],
        ];
    }
}
