<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\NettMail\Models\Sender;

/** @extends Factory<Sender> */
class SenderFactory extends Factory
{
    protected $model = Sender::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'from_email' => $this->faker->safeEmail(),
            'from_name' => $this->faker->name(),
            'driver' => 'php',
            'config' => [],
            'bounce_mailbox' => null,
        ];
    }
}
