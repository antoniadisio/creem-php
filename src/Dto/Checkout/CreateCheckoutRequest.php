<?php

declare(strict_types=1);

namespace Creem\Dto\Checkout;

use Creem\Dto\Common\CustomFieldInput;
use Creem\Internal\Serialization\RequestValueNormalizer;

final class CreateCheckoutRequest
{
    /**
     * @param  list<CustomFieldInput>  $customFields
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly string $productId,
        public readonly ?string $requestId = null,
        public readonly ?int $units = null,
        public readonly ?string $discountCode = null,
        public readonly ?CheckoutCustomerInput $customer = null,
        public readonly array $customFields = [],
        public readonly ?string $successUrl = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'request_id' => $this->requestId,
            'product_id' => $this->productId,
            'units' => $this->units,
            'discount_code' => $this->discountCode,
            'customer' => $this->customer,
            'custom_fields' => $this->customFields,
            'success_url' => $this->successUrl,
            'metadata' => $this->metadata,
        ]);
    }
}
