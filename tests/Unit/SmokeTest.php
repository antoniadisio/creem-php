<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_phase_two_contract_assets_exist(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);

        self::assertFileExists($repositoryRoot.'/spec/creem-openapi.json');
        self::assertFileExists($repositoryRoot.'/docs/openapi-audit.md');
    }
}
