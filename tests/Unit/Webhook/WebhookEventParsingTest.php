<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Common\StructuredObject;
use Creem\Dto\Webhook\WebhookEvent;
use Creem\Exception\HydrationException;
use Creem\Exception\InvalidWebhookPayloadException;
use Creem\Exception\InvalidWebhookSignatureException;
use Creem\Exception\TransportException;
use Creem\Tests\Support\WebhookTestSupport;
use Creem\Webhook;

use function str_repeat;

test('webhook event parsing hydrates documented envelopes into typed wrappers', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","created_at":"2026-03-04T12:34:56+00:00","object":{"id":"lic_123","active":true}}';

    $event = Webhook::parseEvent($payload);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->id())->toBe('evt_123')
        ->and($event->eventType())->toBe('license.created')
        ->and($event->createdAt()->format(DATE_ATOM))->toBe('2026-03-04T12:34:56+00:00')
        ->and($event->object())->toBeInstanceOf(StructuredObject::class)
        ->and($event->object()->get('id'))->toBe('lic_123')
        ->and($event->payload()->get('object'))->toBeInstanceOf(StructuredObject::class)
        ->and($event->toArray()['object'])->toBeInstanceOf(StructuredObject::class);
});

test('webhook event parsing keeps unknown event types as raw strings', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created.preview.v2","created_at":"2026-03-04T12:34:56+00:00","object":{"id":"lic_123"}}';

    $event = Webhook::parseEvent($payload);

    expect($event->eventType())->toBe('license.created.preview.v2');
});

test('webhook event parsing throws payload exceptions for malformed json instead of transport exceptions', function (): void {
    $thrown = null;

    try {
        Webhook::parseEvent('{"id":');
    } catch (\Throwable $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(InvalidWebhookPayloadException::class);
    expect($thrown instanceof TransportException)->toBeFalse();

    if (! $thrown instanceof InvalidWebhookPayloadException) {
        return;
    }

    expect($thrown->getMessage())->toBe('The Creem webhook payload is not valid JSON.');
});

test('webhook event parsing rejects payloads that exceed the size limit before decoding', function (): void {
    $payload = str_repeat('a', 1_048_577);

    expect(static fn (): WebhookEvent => Webhook::parseEvent($payload))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload exceeds the 1048576 byte limit.');
});

test('webhook event parsing rejects payloads with missing envelope fields', function (): void {
    expect(static fn (): WebhookEvent => Webhook::parseEvent(
        '{"id":"evt_123","created_at":"2026-03-04T12:34:56+00:00","object":{"id":"lic_123"}}'
    ))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload is not a valid event object.');
});

test('webhook event parsing preserves hydration failures for malformed envelope fields', function (): void {
    $thrown = null;

    try {
        Webhook::parseEvent(
            '{"id":"evt_123","eventType":"license.created","created_at":"2026-03-04T12:34:56+00:00","object":[]}'
        );
    } catch (\Throwable $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(InvalidWebhookPayloadException::class);

    if (! $thrown instanceof InvalidWebhookPayloadException) {
        return;
    }

    expect($thrown->getPrevious())->toBeInstanceOf(HydrationException::class);
});

test('webhook construction verifies signatures before parsing events', function (): void {
    $payload = '{"id":';
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload, signature: 'invalid');

    expect(static fn (): WebhookEvent => Webhook::constructEvent($payload, $signature, 'whsec_test_secret'))
        ->toThrow(InvalidWebhookSignatureException::class);
});

test('webhook construction throws payload exceptions for malformed verified payloads', function (): void {
    $payload = '{"id":';
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload);

    expect(static fn (): WebhookEvent => Webhook::constructEvent($payload, $signature, 'whsec_test_secret'))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload is not valid JSON.');
});

test('webhook construction builds verified events without a client instance', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","created_at":"2026-03-04T12:34:56+00:00","object":{"id":"lic_123"}}';
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload);

    $event = Webhook::constructEvent($payload, $signature, 'whsec_test_secret');

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->id())->toBe('evt_123')
        ->and($event->object()->get('id'))->toBe('lic_123');
});

test('webhook construction rejects replayed events when the replay callback returns true', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","created_at":"2026-03-04T12:34:56+00:00","object":{"id":"lic_123"}}';
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload);
    $receivedEventId = null;

    expect(static function () use ($payload, $signature, &$receivedEventId): void {
        Webhook::constructEvent(
            $payload,
            $signature,
            'whsec_test_secret',
            static function (WebhookEvent $event) use (&$receivedEventId): bool {
                $receivedEventId = $event->id();

                return true;
            },
        );
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook event was already processed.');

    expect($receivedEventId)->toBe('evt_123');
});
