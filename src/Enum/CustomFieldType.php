<?php

declare(strict_types=1);

namespace Creem\Enum;

enum CustomFieldType: string
{
    case Text = 'text';
    case Checkbox = 'checkbox';
}
