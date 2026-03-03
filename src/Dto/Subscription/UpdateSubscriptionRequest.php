<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Enum\SubscriptionUpdateBehavior;
use Creem\Internal\Serialization\RequestValueNormalizer;

final class UpdateSubscriptionRequest
{
    /**
     * @param  list<UpsertSubscriptionItem>  $items
     */
    public function __construct(
        public readonly array $items = [],
        public readonly ?SubscriptionUpdateBehavior $updateBehavior = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'items' => $this->items,
            'update_behavior' => $this->updateBehavior,
        ]);
    }
}
