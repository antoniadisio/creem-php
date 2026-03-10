<?php

declare(strict_types=1);

namespace Creem\Enum;

enum DiscountStatus: string
{
    case Active = 'active';
    case Deleted = 'deleted';
    case Draft = 'draft';
    case Expired = 'expired';
    case Scheduled = 'scheduled';
}
