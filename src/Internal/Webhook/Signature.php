<?php

declare(strict_types=1);

namespace Creem\Internal\Webhook;

use Creem\Exception\InvalidWebhookSignatureException;

use function hash_hmac;
use function trim;

final class Signature
{
    public static function compute(string $payload, #[\SensitiveParameter]
        string $secret): string
    {
        $normalizedSecret = trim($secret);

        if ($normalizedSecret === '') {
            throw InvalidWebhookSignatureException::missingSecret();
        }

        return hash_hmac('sha256', $payload, $normalizedSecret);
    }
}
