<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Enum\SubscriptionUpdateBehavior;
use Creem\Internal\Serialization\RequestValueNormalizer;

final class UpgradeSubscriptionRequest
{
    public function __construct(
        public readonly string $productId,
        public readonly ?SubscriptionUpdateBehavior $updateBehavior = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'product_id' => $this->productId,
            'update_behavior' => $this->updateBehavior,
        ]);
    }
}
