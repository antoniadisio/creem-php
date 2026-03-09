<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use DateTimeImmutable;

test('transaction search request dtos serialize pagination and filters', function (): void {
    expect(new SearchTransactionsRequest('cus_123', 'ord_123', 'prod_123', 3, 25)->toQuery())->toBe([
        'customer_id' => 'cus_123',
        'order_id' => 'ord_123',
        'product_id' => 'prod_123',
        'page_number' => 3,
        'page_size' => 25,
    ]);
});

test('stats request dtos serialize millisecond timestamps', function (): void {
    $request = new GetStatsSummaryRequest(
        CurrencyCode::USD,
        new DateTimeImmutable('2023-11-14T22:13:20.123+00:00'),
        new DateTimeImmutable('2023-11-15T22:13:20.456+00:00'),
        StatsInterval::Week,
    );

    expect($request->toQuery())->toBe([
        'startDate' => 1700000000123,
        'endDate' => 1700086400456,
        'interval' => 'week',
        'currency' => 'USD',
    ]);
});
