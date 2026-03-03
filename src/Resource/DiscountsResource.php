<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Internal\Http\CreemConnector;

final class DiscountsResource
{
    public function __construct(
        private readonly CreemConnector $connector,
    ) {}
}
