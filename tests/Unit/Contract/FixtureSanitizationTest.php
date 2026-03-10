<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

use Creem\Tests\TestCase;

use function filter_var;
use function is_array;
use function is_int;
use function is_string;
use function parse_url;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

test('response fixtures use sanitized placeholder values', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;

    foreach ($testCase->responseFixtureCatalog()->names() as $fixture) {
        assertSanitizedFixtureValue(
            $testCase,
            $fixture,
            $testCase->fixture($fixture),
            $fixture,
        );
    }
});

function assertSanitizedFixtureValue(TestCase $testCase, string $fixture, mixed $value, string $path): void
{
    if (is_array($value)) {
        foreach ($value as $key => $nestedValue) {
            assertSanitizedFixtureValue($testCase, $fixture, $nestedValue, sprintf('%s.%s', $path, (string) $key));
        }

        return;
    }

    if (is_string($value)) {
        if (str_starts_with($value, 'sk_') || str_starts_with($value, 'creem_')) {
            $testCase->fail(sprintf('%s contains a live-looking secret at %s.', $fixture, $path));
        }

        if (preg_match('/^(ch|cust|dis|lk|lki|ord|pprice|prod|sitem|sto|sub|tran)_/', $value) === 1) {
            $testCase->assertMatchesRegularExpression(
                '/^(ch|cust|dis|lk|lki|ord|pprice|prod|sitem|sto|sub|tran)_fixture_[a-z0-9_]+$/',
                $value,
                sprintf('%s contains a non-placeholder fixture identifier at %s.', $fixture, $path),
            );
        }

        if (str_contains($value, '@')) {
            $testCase->assertMatchesRegularExpression(
                '/^[a-z0-9._%+-]+@example\.test$/i',
                $value,
                sprintf('%s contains a non-sanitized email at %s.', $fixture, $path),
            );
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $host = parse_url($value, PHP_URL_HOST);

            $testCase->assertIsString($host, sprintf('%s contains an invalid URL at %s.', $fixture, $path));
            $testCase->assertTrue(
                $host === 'creem.io' || str_ends_with($host, '.example'),
                sprintf('%s contains a non-placeholder URL host at %s.', $fixture, $path),
            );
        }

        if (isCanonicalTimestampPath($path)) {
            $testCase->assertContains(
                $value,
                canonicalIsoTimestamps(),
                sprintf('%s contains a non-canonical timestamp at %s.', $fixture, $path),
            );
        }

        return;
    }

    if (is_int($value) && isCanonicalTimestampPath($path)) {
        $testCase->assertContains(
            $value,
            canonicalUnixTimestamps(),
            sprintf('%s contains a non-canonical Unix timestamp at %s.', $fixture, $path),
        );
    }
}

function isCanonicalTimestampPath(string $path): bool
{
    return array_any([
        'timestamp',
        'created_at',
        'updated_at',
        'expires_at',
        'expiry_date',
        'last_transaction_date',
        'next_transaction_date',
        'current_period_start_date',
        'current_period_end_date',
        'period_start',
        'period_end',
    ], fn ($segment): bool => str_ends_with($path, '.'.$segment));
}

/**
 * @return non-empty-list<string>
 */
function canonicalIsoTimestamps(): array
{
    return [
        '2026-03-07T06:35:39.943Z',
        '2026-03-07T06:35:41.762Z',
        '2026-03-07T06:49:22.500Z',
        '2026-03-07T06:49:26.257Z',
        '2026-03-07T06:50:38.000Z',
        '2026-03-07T06:50:41.456Z',
        '2026-03-07T06:50:41.467Z',
        '2026-03-07T06:50:46.748Z',
        '2026-03-07T06:51:33.311Z',
        '2026-03-07T06:51:33.586Z',
        '2026-03-10T08:08:03.048Z',
        '2026-03-10T08:43:11.285Z',
        '2026-04-07T06:50:38.000Z',
    ];
}

/**
 * @return non-empty-list<int>
 */
function canonicalUnixTimestamps(): array
{
    return [
        1763337600000,
        1772866238000,
        1772866243426,
        1775544638000,
    ];
}
