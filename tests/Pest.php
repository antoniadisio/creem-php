<?php

declare(strict_types=1);

use Creem\Tests\IntegrationTestCase;
use Creem\Tests\SmokeTestCase;
use Creem\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit');
pest()->extend(IntegrationTestCase::class)->in('Integration');
pest()->extend(SmokeTestCase::class)->in('Smoke');
