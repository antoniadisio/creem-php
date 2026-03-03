<?php

declare(strict_types=1);

namespace Creem\Internal\Http;

use Creem\Exception\AuthenticationException;
use Creem\Exception\CreemException;
use Creem\Exception\NotFoundException;
use Creem\Exception\RateLimitException;
use Creem\Exception\ServerException;
use Creem\Exception\ValidationException;
use Saloon\Http\Response;
use Throwable;

use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;
use function trim;

final class ExceptionMapper
{
    public static function map(Response $response, ?Throwable $previous = null): CreemException
    {
        $statusCode = $response->status();
        $context = self::buildContext($response);
        $message = self::resolveMessage($statusCode, $context);

        if (self::isValidationFailure($statusCode, $context)) {
            return new ValidationException($message, $statusCode, $context, $previous);
        }

        return match (true) {
            $statusCode === 401 || $statusCode === 403 => new AuthenticationException($message, $statusCode, $context, $previous),
            $statusCode === 404 => new NotFoundException($message, $statusCode, $context, $previous),
            $statusCode === 429 => new RateLimitException($message, $statusCode, $context, $previous),
            $statusCode >= 500 => new ServerException($message, $statusCode, $context, $previous),
            default => new CreemException($message, $statusCode, $context, $previous),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildContext(Response $response): array
    {
        $body = trim($response->body());

        if ($body === '') {
            return [];
        }

        if (self::shouldDecodeJson($response, $body)) {
            /** @var array<string, mixed> $context */
            $context = ResponseDecoder::decode($response);

            return $context;
        }

        return ['body' => $body];
    }

    private static function shouldDecodeJson(Response $response, string $body): bool
    {
        return $response->isJson() || str_starts_with($body, '{') || str_starts_with($body, '[');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function isValidationFailure(int $statusCode, array $context): bool
    {
        return $statusCode === 422
            || ($statusCode >= 400 && $statusCode < 500 && isset($context['errors']) && is_array($context['errors']));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function resolveMessage(int $statusCode, array $context): string
    {
        $message = self::extractMessage($context);

        if ($message !== null) {
            return $message;
        }

        return sprintf('The Creem API request failed with status %d.', $statusCode);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function extractMessage(array $context): ?string
    {
        foreach (['message', 'error', 'detail', 'title', 'body'] as $key) {
            $value = $context[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $errors = $context['errors'] ?? null;

        if (is_string($errors) && trim($errors) !== '') {
            return trim($errors);
        }

        if (! is_array($errors)) {
            return null;
        }

        return self::extractMessageFromErrors($errors);
    }

    /**
     * @param  array<array-key, mixed>  $errors
     */
    private static function extractMessageFromErrors(array $errors): ?string
    {
        foreach ($errors as $error) {
            if (is_string($error) && trim($error) !== '') {
                return trim($error);
            }

            if (! is_array($error)) {
                continue;
            }

            foreach (['message', 'detail', 'error'] as $key) {
                $value = $error[$key] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }

            $nestedMessage = self::extractMessageFromErrors($error);

            if ($nestedMessage !== null) {
                return $nestedMessage;
            }
        }

        return null;
    }
}
