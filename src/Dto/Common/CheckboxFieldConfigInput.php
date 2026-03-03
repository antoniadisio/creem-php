<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Internal\Serialization\RequestValueNormalizer;

final class CheckboxFieldConfigInput
{
    public function __construct(
        public readonly ?string $label = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'label' => $this->label,
        ]);
    }
}
