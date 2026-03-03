<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Environment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_config_applies_overrides_and_normalizes_inputs(): void
    {
        $config = new Config(
            '  sk_test_123  ',
            Environment::Test,
            'https://example.test/',
            15,
            '  integration-suite  ',
        );

        self::assertSame('sk_test_123', $config->apiKey());
        self::assertSame(Environment::Test, $config->environment());
        self::assertSame('https://example.test', $config->baseUrl());
        self::assertSame('https://example.test', $config->resolveBaseUrl());
        self::assertSame(15.0, $config->timeout());
        self::assertSame('integration-suite', $config->userAgentSuffix());
        self::assertStringStartsWith('creem-php-sdk/', $config->userAgent());
        self::assertStringContainsString('php/'.PHP_VERSION, $config->userAgent());
        self::assertStringEndsWith('integration-suite', $config->userAgent());
    }

    public function test_config_rejects_invalid_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('');
    }
}
