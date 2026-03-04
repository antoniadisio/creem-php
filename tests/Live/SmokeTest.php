<?php

declare(strict_types=1);

namespace Creem\Tests\Live;

use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Product\Product;
use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsSummary;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use Creem\Exception\AuthenticationException;
use Creem\Tests\LiveTestCase;

test('live auth smoke maps invalid api keys to authentication exceptions', function (): void {
    /** @var LiveTestCase $this */
    [$start, $end] = $this->liveWindow();
    $client = $this->liveClient('sk_test_invalid_pest_smoke');

    expect(static fn (): StatsSummary => $client->stats()->summary(
        new GetStatsSummaryRequest(CurrencyCode::USD, $start, $end, StatsInterval::Day),
    ))->toThrow(AuthenticationException::class);
});

test('live stats smoke returns a typed summary from the test environment', function (): void {
    /** @var LiveTestCase $this */
    [$start, $end] = $this->liveWindow();
    $summary = $this->liveClient()->stats()->summary(
        new GetStatsSummaryRequest(CurrencyCode::USD, $start, $end, StatsInterval::Day),
    );

    expect($summary)->toBeInstanceOf(StatsSummary::class)
        ->and($summary->totals)->not->toBeNull()
        ->and($summary->periods)->toBeArray();
});

test('live product search smoke returns a typed page from the test environment', function (): void {
    /** @var LiveTestCase $this */
    $page = $this->liveClient()->products()->search();

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class);

    if ($page->get(0) !== null) {
        expect($page->get(0))->toBeInstanceOf(Product::class);
    }
});
