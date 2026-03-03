<?php

declare(strict_types=1);

namespace Creem\Dto\Discount;

use Creem\Enum\CurrencyCode;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountType;
use Creem\Internal\Serialization\RequestValueNormalizer;
use DateTimeImmutable;

final class CreateDiscountRequest
{
    /**
     * @param  list<string>  $appliesToProducts
     */
    public function __construct(
        public readonly string $name,
        public readonly DiscountType $type,
        public readonly DiscountDuration $duration,
        public readonly array $appliesToProducts,
        public readonly ?string $code = null,
        public readonly ?int $amount = null,
        public readonly ?CurrencyCode $currency = null,
        public readonly ?int $percentage = null,
        public readonly ?DateTimeImmutable $expiryDate = null,
        public readonly ?int $maxRedemptions = null,
        public readonly ?int $durationInMonths = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'percentage' => $this->percentage,
            'expiry_date' => RequestValueNormalizer::rfc3339($this->expiryDate),
            'max_redemptions' => $this->maxRedemptions,
            'duration' => $this->duration,
            'duration_in_months' => $this->durationInMonths,
            'applies_to_products' => $this->appliesToProducts,
        ]);
    }
}
