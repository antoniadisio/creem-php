<?php

declare(strict_types=1);

namespace Creem\Enum;

enum LicenseInstanceStatus: string
{
    case Active = 'active';
    case Deactivated = 'deactivated';
}
