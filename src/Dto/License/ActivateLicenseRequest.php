<?php

declare(strict_types=1);

namespace Creem\Dto\License;

use Creem\Internal\Serialization\RequestValueNormalizer;

final class ActivateLicenseRequest
{
    public function __construct(
        public readonly string $key,
        public readonly string $instanceName,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'key' => $this->key,
            'instance_name' => $this->instanceName,
        ]);
    }
}
