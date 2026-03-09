<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Customer\Customer;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Dto\Product\Product;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsSummary;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Dto\Transaction\Transaction;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use Creem\Exception\AuthenticationException;
use Creem\Tests\SmokeTestCase;

/**
 * @template TItem of object
 *
 * @param  Page<TItem>  $page
 * @param  class-string<TItem>  $itemClass
 */
function expectTypedPage(Page $page, string $itemClass): void
{
    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class);

    $item = $page->get(0);

    if ($item !== null) {
        expect($item)->toBeInstanceOf($itemClass);
    }
}

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
    $page = $this->smokeClient()->products()->search(new SearchProductsRequest(pageSize: 1));

    expectTypedPage($page, Product::class);
});

test('smoke customers list returns a typed page', function (): void {
    /** @var SmokeTestCase $this */
    $page = $this->smokeClient()->customers()->list(new ListCustomersRequest(pageSize: 1));

    expectTypedPage($page, Customer::class);
});

test('smoke transactions search returns a typed page', function (): void {
    /** @var SmokeTestCase $this */
    $page = $this->smokeClient()->transactions()->search(new SearchTransactionsRequest(pageSize: 1));

    expectTypedPage($page, Transaction::class);
});
