<?php

declare(strict_types=1);

namespace Creem\Enum;

enum ProductStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
