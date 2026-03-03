<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Enum\CustomFieldType;
use Creem\Internal\Serialization\RequestValueNormalizer;

final class CustomFieldInput
{
    public function __construct(
        public readonly CustomFieldType $type,
        public readonly string $key,
        public readonly string $label,
        public readonly ?bool $optional = null,
        public readonly ?TextFieldConfigInput $text = null,
        public readonly ?CheckboxFieldConfigInput $checkbox = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'type' => $this->type,
            'key' => $this->key,
            'label' => $this->label,
            'optional' => $this->optional,
            'text' => $this->text,
            'checkbox' => $this->checkbox,
        ]);
    }
}
