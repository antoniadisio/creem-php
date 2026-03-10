<?php

declare(strict_types=1);

namespace Creem\Tests;

use Creem\Tests\Support\Contract\InteractsWithContractSupport;
use Creem\Tests\Support\InteractsWithFixtures;
use Creem\Tests\Support\InteractsWithMockRequests;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithContractSupport;
    use InteractsWithFixtures;
    use InteractsWithMockRequests;
}
