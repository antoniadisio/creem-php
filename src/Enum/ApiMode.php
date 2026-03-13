<?php

declare(strict_types=1);

namespace Creem\Enum;

enum ApiMode: string
{
    case Test = 'test';
    case Production = 'prod';
}
