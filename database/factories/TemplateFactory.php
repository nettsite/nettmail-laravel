<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nettsite\NettMail\Core\Domain\Templates\TemplateType;
use NettSite\NettMail\Models\Template;

/** @extends Factory<Template> */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => TemplateType::Broadcast,
            'subject' => $this->faker->sentence(),
            'design' => [],
            'html' => '<p>{{unsubscribe_url}}</p>',
            'plain_text' => 'Unsubscribe: {{unsubscribe_url}}',
        ];
    }
}
