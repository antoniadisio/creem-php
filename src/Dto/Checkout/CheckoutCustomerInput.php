<?php

declare(strict_types=1);

namespace Creem\Dto\Checkout;

use Creem\Internal\Serialization\RequestValueNormalizer;

final class CheckoutCustomerInput
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $email = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'id' => $this->id,
            'email' => $this->email,
        ]);
    }
}
