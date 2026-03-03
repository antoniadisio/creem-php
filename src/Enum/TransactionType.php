<?php

declare(strict_types=1);

namespace Creem\Enum;

enum TransactionType: string
{
    case Payment = 'payment';
    case Invoice = 'invoice';
}
