<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Enum\Environment;
use Creem\Internal\Http\CreemConnector;
use Creem\Tests\Support\HttpTestSupport;

test('connector builds expected headers and hardened request configuration', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123', Environment::Test, null, 12.5, 'integration-suite'));
    $pendingRequest = $connector->createPendingRequest(HttpTestSupport::pingRequest());
    $psrRequest = $pendingRequest->createPsrRequest();
    $requestConfig = $pendingRequest->config()->all();

    expect((string) $psrRequest->getUri())->toBe('https://test-api.creem.io/v1/ping')
        ->and($psrRequest->getHeaderLine('Accept'))->toBe('application/json')
        ->and($psrRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($psrRequest->getHeaderLine('x-api-key'))->toBe('sk_test_123')
        ->and($psrRequest->getHeaderLine('User-Agent'))->toStartWith('creem-php-sdk/')
        ->and($psrRequest->getHeaderLine('User-Agent'))->toContain('integration-suite')
        ->and($requestConfig['allow_redirects'])->toBeFalse()
        ->and($requestConfig['verify'])->toBeTrue()
        ->and($requestConfig['timeout'])->toBe(12.5)
        ->and($requestConfig['connect_timeout'])->toBe(12.5)
        ->and($requestConfig['read_timeout'])->toBe(12.5)
        ->and($requestConfig['crypto_method'])->toBe(STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
});

test('connector applies the default timeout when none is configured', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $pendingRequest = $connector->createPendingRequest(HttpTestSupport::pingRequest());
    $requestConfig = $pendingRequest->config()->all();

    expect($requestConfig['timeout'])->toBe(Config::DEFAULT_TIMEOUT_SECONDS)
        ->and($requestConfig['connect_timeout'])->toBe(Config::DEFAULT_TIMEOUT_SECONDS)
        ->and($requestConfig['read_timeout'])->toBe(Config::DEFAULT_TIMEOUT_SECONDS);
});
