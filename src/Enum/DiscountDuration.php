<?php

declare(strict_types=1);

namespace Creem\Enum;

enum DiscountDuration: string
{
    case Forever = 'forever';
    case Once = 'once';
    case Repeating = 'repeating';
}
