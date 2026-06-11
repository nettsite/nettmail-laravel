<?php

namespace NettSite\NettMail\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;

/** @extends Factory<ListContact> */
class ListContactFactory extends Factory
{
    protected $model = ListContact::class;

    public function definition(): array
    {
        return [
            'list_id' => MailingList::factory(),
            'contact_id' => Contact::factory(),
            'status' => MembershipStatus::Subscribed,
            'tags' => [],
            'subscribed_at' => now(),
        ];
    }
}
