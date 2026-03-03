<?php

declare(strict_types=1);

namespace Creem\Enum;

enum SubscriptionCancellationMode: string
{
    case Immediate = 'immediate';
    case Scheduled = 'scheduled';
}
