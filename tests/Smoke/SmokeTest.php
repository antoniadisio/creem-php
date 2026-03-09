<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Product\Product;
use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsSummary;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use Creem\Exception\AuthenticationException;
use Creem\Tests\SmokeTestCase;

test('smoke maps an invalid api key to an authentication exception', function (): void {
    /** @var SmokeTestCase $this */
    $this->requireSmokeApiKey();
    [$start, $end] = $this->smokeWindow();
    $client = $this->smokeClient('sk_test_invalid_pest_smoke');

    expect(static fn (): StatsSummary => $client->stats()->summary(
        new GetStatsSummaryRequest(CurrencyCode::USD, $start, $end, StatsInterval::Day),
    ))->toThrow(AuthenticationException::class);
});

test('smoke returns a typed stats summary', function (): void {
    /** @var SmokeTestCase $this */
    [$start, $end] = $this->smokeWindow();
    $summary = $this->smokeClient()->stats()->summary(
        new GetStatsSummaryRequest(CurrencyCode::USD, $start, $end, StatsInterval::Day),
    );

    expect($summary)->toBeInstanceOf(StatsSummary::class)
        ->and($summary->totals)->not->toBeNull()
        ->and($summary->periods)->toBeArray();
});

test('smoke product search returns a typed page', function (): void {
    /** @var SmokeTestCase $this */
    $page = $this->smokeClient()->products()->search();

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class);

    if ($page->get(0) !== null) {
        expect($page->get(0))->toBeInstanceOf(Product::class);
    }
});
