<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Common\Pagination;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Dto\Transaction\Transaction;
use Creem\Enum\CurrencyCode;
use Creem\Enum\TransactionStatus;
use Creem\Resource\TransactionsResource;
use Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('transactions resource gets and searches transactions', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('transaction.json')),
        MockResponse::make($this->responseFixture('transaction_page.json')),
    ]);
    $resource = new TransactionsResource($this->connector($mockClient));

    $transaction = $resource->get('txn_fixture_paid');

    expect($transaction->id)->toBe('txn_fixture_paid')
        ->and($transaction->currency)->toBe(CurrencyCode::USD)
        ->and($transaction->status)->toBe(TransactionStatus::Paid);
    $this->assertRequest($mockClient, Method::GET, '/v1/transactions', ['transaction_id' => 'txn_fixture_paid']);

    $page = $resource->search(new SearchTransactionsRequest(customerId: 'cus_fixture_taylor', pageNumber: 3, pageSize: 25));

    expect($page->count())->toBe(1)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(3)
        ->and($page->pagination?->nextPage)->toBeNull()
        ->and($page->get(0))->toBeInstanceOf(Transaction::class)
        ->and($page->get(0)?->status)->toBe(TransactionStatus::Paid);
    $this->assertRequest(
        $mockClient,
        Method::GET,
        '/v1/transactions/search',
        ['customer_id' => 'cus_fixture_taylor', 'page_number' => '3', 'page_size' => '25'],
    );
});

test('transactions resource omits query parameters when search request is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('transaction_page.json')),
    ]);
    $resource = new TransactionsResource($this->connector($mockClient));

    $page = $resource->search();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Transaction::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/transactions/search');
});
