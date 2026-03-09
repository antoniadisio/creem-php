<?php

declare(strict_types=1);

namespace Creem\Tests\Support;

use Creem\Internal\Webhook\Signature;

use function sprintf;
use function time;

final class WebhookTestSupport
{
    public static function timestampedSignatureHeader(
        string $payload,
        #[\SensitiveParameter]
        string $secret = 'whsec_test_secret',
        ?int $timestamp = null,
        ?string $signature = null,
    ): string {
        $timestamp ??= time();
        $signature ??= Signature::compute($payload, $secret, $timestamp);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }
}
