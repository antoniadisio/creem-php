<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

use Creem\Tests\TestCase;

use function basename;
use function filter_var;
use function glob;
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

    foreach (glob($testCase->fixturesDirectory().'/*.json') ?: [] as $path) {
        assertSanitizedFixtureValue(
            $testCase,
            basename($path),
            $testCase->fixture(basename($path)),
            basename($path),
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

        if (preg_match('/^(chk|cus|disc|feat|file|ins|item|lic|ord|price|prod|req|sub|txn)_/', $value) === 1) {
            $testCase->assertMatchesRegularExpression(
                '/^(chk|cus|disc|feat|file|ins|item|lic|ord|price|prod|req|sub|txn)_fixture_[a-z0-9_]+$/',
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
                str_ends_with($host, '.example'),
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
        '2025-01-15T10:00:00Z',
        '2025-01-15T10:05:00Z',
        '2025-01-15T12:00:00Z',
        '2025-02-15T10:00:00Z',
        '2025-12-31T23:59:59Z',
        '2026-01-15T10:00:00Z',
    ];
}

/**
 * @return non-empty-list<int>
 */
function canonicalUnixTimestamps(): array
{
    return [
        1736935200,
        1736935200000,
        1736935500,
        1736935500000,
        1736942400,
        1736942400000,
        1739613600,
        1739613600000,
        1767225599,
        1767225599000,
        1768471200,
        1768471200000,
    ];
}
