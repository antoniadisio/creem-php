<?php

declare(strict_types=1);

use Creem\Tests\IntegrationTestCase;
use Creem\Tests\LiveTestCase;
use Creem\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit');
pest()->extend(IntegrationTestCase::class)->in('Integration');
pest()->extend(LiveTestCase::class)->in('Live');
