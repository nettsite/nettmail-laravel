<?php

namespace NettSite\NettMail\Campaigns;

use Illuminate\Support\LazyCollection;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignSender;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentEvaluator;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentGroup;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\ListContact;

/**
 * Resolves the subscribed, non-suppressed members of a campaign's list,
 * optionally filtered by its segment.
 */
final class CampaignAudienceResolver
{
    public function __construct(
        private readonly SegmentEvaluator $evaluator = new SegmentEvaluator,
        private readonly CampaignSender $sender = new CampaignSender,
        private readonly SegmentConditionHydrator $hydrator = new SegmentConditionHydrator,
    ) {}

    /**
     * @return LazyCollection<int, ListContact>
     */
    public function resolve(Campaign $campaign): LazyCollection
    {
        $segmentGroup = $this->segmentGroup($campaign);

        return ListContact::query()
            ->where('list_id', $campaign->list_id)
            ->where('status', MembershipStatus::Subscribed)
            ->with('contact')
            ->lazy(200)
            ->filter(function (ListContact $membership) use ($segmentGroup): bool {
                if (! $this->sender->shouldSend($membership->contact->toDomain())) {
                    return false;
                }

                if ($segmentGroup === null) {
                    return true;
                }

                return $this->evaluator->evaluate($segmentGroup, $this->fieldsFor($membership));
            });
    }

    private function segmentGroup(Campaign $campaign): ?SegmentGroup
    {
        $segment = $campaign->segment;

        if ($segment === null) {
            return null;
        }

        return $this->hydrator->hydrate($segment->conditions ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFor(ListContact $membership): array
    {
        $contact = $membership->contact;

        return array_merge($contact->metadata ?? [], [
            'email' => $contact->email,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'phone' => $contact->phone,
            'tags' => implode(',', $membership->tags ?? []),
            'subscribed_at' => $membership->subscribed_at?->toDateTimeImmutable(),
        ]);
    }
}
