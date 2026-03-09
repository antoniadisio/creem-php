<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsPeriod;
use Creem\Dto\Stats\StatsTotals;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use Creem\Resource\StatsResource;
use Creem\Tests\IntegrationTestCase;
use DateTimeImmutable;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('stats resource returns typed summary data', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('stats_summary.json')),
    ]);
    $resource = new StatsResource($this->connector($mockClient));

    $summary = $resource->summary(
        new GetStatsSummaryRequest(
            CurrencyCode::USD,
            new DateTimeImmutable('@1736935200'),
            new DateTimeImmutable('@1739613600'),
            StatsInterval::Day,
        ),
    );

    expect($summary->totals)->toBeInstanceOf(StatsTotals::class)
        ->and($summary->totals?->totalRevenue)->toBe(12000)
        ->and($summary->periods)->toHaveCount(1)
        ->and($summary->periods[0] ?? null)->toBeInstanceOf(StatsPeriod::class)
        ->and($summary->periods[0]->timestamp ?? null)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($summary->periods[0]->timestamp?->format(DATE_ATOM))->toBe('2025-01-15T10:00:00+00:00')
        ->and($summary->periods[0]->netRevenue ?? null)->toBe(11500);
    $this->assertRequest(
        $mockClient,
        Method::GET,
        '/v1/stats/summary',
        ['startDate' => '1736935200000', 'endDate' => '1739613600000', 'interval' => 'day', 'currency' => 'USD'],
    );
});
