<?php

declare(strict_types=1);

namespace Creem\Dto\Product;

use Creem\Dto\Common\CustomFieldInput;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CurrencyCode;
use Creem\Enum\TaxCategory;
use Creem\Enum\TaxMode;
use Creem\Internal\Serialization\RequestValueNormalizer;

final class CreateProductRequest
{
    /**
     * @param  list<CustomFieldInput>  $customFields
     */
    public function __construct(
        public readonly string $name,
        public readonly int $price,
        public readonly CurrencyCode $currency,
        public readonly BillingType $billingType,
        public readonly ?string $description = null,
        public readonly ?string $imageUrl = null,
        public readonly ?BillingPeriod $billingPeriod = null,
        public readonly ?TaxMode $taxMode = null,
        public readonly ?TaxCategory $taxCategory = null,
        public readonly ?string $defaultSuccessUrl = null,
        public readonly array $customFields = [],
        public readonly ?bool $abandonedCartRecoveryEnabled = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_type' => $this->billingType,
            'billing_period' => $this->billingPeriod,
            'tax_mode' => $this->taxMode,
            'tax_category' => $this->taxCategory,
            'default_success_url' => $this->defaultSuccessUrl,
            'custom_fields' => $this->customFields,
            'abandoned_cart_recovery_enabled' => $this->abandonedCartRecoveryEnabled,
        ]);
    }
}
