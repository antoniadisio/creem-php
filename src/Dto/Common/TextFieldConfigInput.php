<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Internal\Serialization\RequestValueNormalizer;

final class TextFieldConfigInput
{
    public function __construct(
        public readonly ?int $maxLength = null,
        public readonly ?int $minLength = null,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        /** @var array<string, int> */
        return RequestValueNormalizer::payload([
            'max_length' => $this->maxLength,
            'min_length' => $this->minLength,
        ]);
    }
}
