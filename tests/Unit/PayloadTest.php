<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;
use Creem\Enum\CurrencyCode;
use Creem\Exception\HydrationException;
use Creem\Internal\Hydration\Payload;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

test('it parses strict scalar types', function (): void {
    $payload = [
        'label' => 'starter',
        'count' => 3,
        'rate' => 1.5,
        'enabled' => true,
    ];

    expect(Payload::string($payload, 'label', 'ExampleDto', true))->toBe('starter')
        ->and(Payload::integer($payload, 'count', 'ExampleDto', true))->toBe(3)
        ->and(Payload::decimal($payload, 'rate', 'ExampleDto', true))->toBe(1.5)
        ->and(Payload::bool($payload, 'enabled', 'ExampleDto', true))->toBeTrue();
});

test('it applies non strict fallbacks for invalid scalar and structured values', function (): void {
    expect(Payload::string(['label' => 123], 'label'))->toBeNull()
        ->and(Payload::number(['price' => 'not-a-number'], 'price'))->toBeNull()
        ->and(Payload::bool(['enabled' => 'yes'], 'enabled'))->toBeNull()
        ->and(Payload::object(['metadata' => ['invalid']], 'metadata'))->toBeNull()
        ->and(Payload::list(['items' => ['invalid' => true]], 'items'))->toBeInstanceOf(StructuredList::class)
        ->and(Payload::list(['items' => ['invalid' => true]], 'items')->count())->toBe(0);
});

test('it coerces numeric strings in non strict number mode', function (): void {
    expect(Payload::number(['price' => '4900'], 'price'))->toBe(4900)
        ->and(Payload::number(['rate' => '12.5'], 'rate'))->toBe(12.5);
});

test('it parses enum values', function (): void {
    expect(Payload::enum(['currency' => 'USD'], 'currency', 'StatsSummary', CurrencyCode::class, true))
        ->toBe(CurrencyCode::USD);
});

test('it parses iso date time values from strings and date objects', function (): void {
    $stringValue = Payload::dateTime(['created_at' => '2026-03-03T10:15:00+00:00'], 'created_at', 'Product', true);
    $immutableValue = Payload::dateTime(['created_at' => new DateTimeImmutable('2026-03-03T10:15:00+00:00')], 'created_at', 'Product', true);
    $mutableValue = Payload::dateTime(
        ['created_at' => new DateTime('2026-03-03T10:15:00+00:00', new DateTimeZone('UTC'))],
        'created_at',
        'Product',
        true,
    );

    expect($stringValue)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($stringValue?->format(DATE_ATOM))->toBe('2026-03-03T10:15:00+00:00')
        ->and($immutableValue)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($immutableValue?->format(DATE_ATOM))->toBe('2026-03-03T10:15:00+00:00')
        ->and($mutableValue)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($mutableValue?->format(DATE_ATOM))->toBe('2026-03-03T10:15:00+00:00');
});

test('it parses millisecond timestamps', function (): void {
    $timestamp = Payload::millisecondsDateTime(['timestamp' => 1700000000000], 'timestamp', 'StatsPeriod', true);

    expect($timestamp)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($timestamp?->format(DATE_ATOM))->toBe('2023-11-14T22:13:20+00:00');
});

test('it maps typed objects lists array objects pages and pagination', function (): void {
    $payload = [
        'totals' => ['total_products' => 2],
        'periods' => [
            ['id' => 'period_1'],
            ['id' => 'period_2'],
        ],
        'metadata' => ['source' => 'sdk-test'],
    ];
    $pagePayload = [
        'items' => [
            ['id' => 'item_1'],
        ],
        'pagination' => [
            'total_records' => 1,
            'total_pages' => 1,
            'current_page' => 1,
            'next_page' => null,
            'prev_page' => null,
        ],
    ];

    $totals = Payload::typedObject(
        $payload,
        'totals',
        'StatsSummary',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );
    $periods = Payload::typedList(
        $payload,
        'periods',
        'StatsSummary',
        static function (mixed $item): string {
            if (! is_array($item) || ! array_key_exists('id', $item) || ! is_string($item['id'])) {
                throw new \RuntimeException('Unexpected period payload.');
            }

            return $item['id'];
        },
        true,
    );
    $metadata = Payload::arrayObject($payload, 'metadata', 'Checkout', true);
    $page = Payload::page(
        $pagePayload,
        static function (array $item): string {
            if (! array_key_exists('id', $item) || ! is_string($item['id'])) {
                throw new \RuntimeException('Unexpected page item payload.');
            }

            return $item['id'];
        },
    );
    $pagination = Payload::pagination($pagePayload, true);

    expect($totals)->toBeInstanceOf(StructuredObject::class)
        ->and($totals?->get('total_products'))->toBe(2)
        ->and($periods)->toBe(['period_1', 'period_2'])
        ->and($metadata)->toBe(['source' => 'sdk-test'])
        ->and($page)->toBeInstanceOf(Page::class)
        ->and($page->count())->toBe(1)
        ->and($page->get(0))->toBe('item_1')
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(1)
        ->and($pagination)->toBeInstanceOf(Pagination::class)
        ->and($pagination?->totalRecords)->toBe(1);
});

test('it maps expandable resources from expanded object payloads', function (): void {
    $product = Payload::expandableResource(
        ['product' => ['id' => 'prod_123', 'name' => 'Starter']],
        'product',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );

    expect($product)->toBeInstanceOf(ExpandableResource::class)
        ->and($product?->id())->toBe('prod_123')
        ->and($product?->isExpanded())->toBeTrue()
        ->and($product?->resource())->toBeInstanceOf(StructuredObject::class);
});

test('it maps expandable resources from id only payloads', function (): void {
    $customer = Payload::expandableResource(
        ['customer' => 'cus_123'],
        'customer',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );

    expect($customer)->toBeInstanceOf(ExpandableResource::class)
        ->and($customer?->id())->toBe('cus_123')
        ->and($customer?->isExpanded())->toBeFalse()
        ->and($customer?->resource())->not->toBeInstanceOf(StructuredObject::class);
});

test('it throws contextual hydration exceptions for invalid required integer fields', function (): void {
    expect(static fn (): ?int => Payload::integer(['price' => '4900'], 'price', 'Product', true))
        ->toThrow(HydrationException::class, 'Hydration failed for Product.price: expected int, got string.');
});

test('it throws contextual hydration exceptions for missing required fields', function (): void {
    expect(static fn (): ?DateTimeImmutable => Payload::dateTime([], 'created_at', 'Product', true))
        ->toThrow(HydrationException::class, 'Hydration failed for Product.created_at: field is required.');
});

test('it throws contextual hydration exceptions for invalid date time strings', function (): void {
    expect(static fn (): ?DateTimeImmutable => Payload::dateTime(['created_at' => 'not-a-date'], 'created_at', 'Product', true))
        ->toThrow(HydrationException::class, 'Hydration failed for Product.created_at: expected a valid date-time string.');
});

test('it throws contextual hydration exceptions for invalid millisecond timestamps', function (): void {
    expect(static fn (): ?DateTimeImmutable => Payload::millisecondsDateTime(['timestamp' => '1700000000000'], 'timestamp', 'StatsPeriod', true))
        ->toThrow(HydrationException::class, 'Hydration failed for StatsPeriod.timestamp: expected int millisecond timestamp, got string.');
});

test('it throws contextual hydration exceptions for invalid enum values', function (): void {
    expect(static fn (): mixed => Payload::enum(['currency' => 'usd'], 'currency', 'StatsSummary', CurrencyCode::class, true))
        ->toThrow(HydrationException::class, 'Hydration failed for StatsSummary.currency: expected valid Creem\Enum\CurrencyCode, got string.');
});

test('it throws contextual hydration exceptions for malformed nested objects', function (): void {
    expect(static fn (): ?object => Payload::typedObject(
        ['totals' => 'invalid'],
        'totals',
        'StatsSummary',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    ))->toThrow(HydrationException::class, 'Hydration failed for StatsSummary.totals: expected object, got string.');
});

test('it throws contextual hydration exceptions for malformed typed lists', function (): void {
    expect(static fn (): array => Payload::typedList(
        ['periods' => 'invalid'],
        'periods',
        'StatsSummary',
        static fn (mixed $item): mixed => $item,
        true,
    ))->toThrow(HydrationException::class, 'Hydration failed for StatsSummary.periods: expected list, got string.');
});

test('it throws contextual hydration exceptions for invalid expandable resource scalars', function (): void {
    expect(static fn (): ?ExpandableResource => Payload::expandableResource(
        ['product' => 123],
        'product',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    ))->toThrow(HydrationException::class, 'Hydration failed for Checkout.product: expected expandable resource string or object, got int.');
});

test('it throws contextual hydration exceptions for expanded resources without ids', function (): void {
    expect(static fn (): ?ExpandableResource => Payload::expandableResource(
        ['product' => ['name' => 'Starter']],
        'product',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    ))->toThrow(HydrationException::class, 'Hydration failed for Checkout.id: field is required.');
});

test('it throws contextual hydration exceptions for malformed page items', function (): void {
    expect(static fn (): Page => Payload::page(
        [
            'items' => ['invalid'],
            'pagination' => [
                'total_records' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'next_page' => null,
                'prev_page' => null,
            ],
        ],
        static fn (array $item): mixed => $item,
    ))->toThrow(HydrationException::class, 'Hydration failed for Page.items: expected object, got string.');
});

test('it throws contextual hydration exceptions for missing pagination fields', function (): void {
    expect(static fn (): ?Pagination => Payload::pagination([
        'pagination' => [
            'total_records' => 1,
            'current_page' => 1,
            'next_page' => null,
            'prev_page' => null,
        ],
    ], true))->toThrow(HydrationException::class, 'Hydration failed for Pagination.total_pages: field is required.');
});

test('it throws contextual hydration exceptions for invalid pagination field types', function (): void {
    expect(static fn (): ?Pagination => Payload::pagination([
        'pagination' => [
            'total_records' => '1',
            'total_pages' => 1,
            'current_page' => 1,
            'next_page' => null,
            'prev_page' => null,
        ],
    ], true))->toThrow(HydrationException::class, 'Hydration failed for Pagination.total_records: expected int, got string.');
});
