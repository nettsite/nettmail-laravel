<?php

namespace NettSite\NettMail\Campaigns;

use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentCondition;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentGroup;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentLogic;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentOperator;

/**
 * Hydrates the JSON shape stored in `nettmail_segments.conditions` into the
 * core segmentation value objects:
 *
 * ```
 * {
 *   "logic": "and"|"or",
 *   "conditions": [
 *     {"field": "...", "operator": "...", "value": ...},
 *     {"logic": "...", "conditions": [...]}
 *   ]
 * }
 * ```
 */
final class SegmentConditionHydrator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function hydrate(array $data): SegmentGroup
    {
        $logic = SegmentLogic::from($data['logic'] ?? 'and');

        $conditions = [];

        foreach ($data['conditions'] ?? [] as $condition) {
            $conditions[] = isset($condition['conditions'])
                ? $this->hydrate($condition)
                : new SegmentCondition(
                    $condition['field'],
                    SegmentOperator::from($condition['operator']),
                    $condition['value'] ?? null,
                );
        }

        return new SegmentGroup($logic, $conditions);
    }
}
