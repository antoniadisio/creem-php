<?php

declare(strict_types=1);

namespace Creem\Enum;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
