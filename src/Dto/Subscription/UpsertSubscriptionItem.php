<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Internal\Serialization\RequestValueNormalizer;

final class UpsertSubscriptionItem
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $productId = null,
        public readonly ?string $priceId = null,
        public readonly ?int $units = null,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        /** @var array<string, string|int> */
        return RequestValueNormalizer::payload([
            'id' => $this->id,
            'product_id' => $this->productId,
            'price_id' => $this->priceId,
            'units' => $this->units,
        ]);
    }
}
