<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Common\Pagination;
use Creem\Dto\Product\CreateProductRequest;
use Creem\Dto\Product\Product;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Enum\ApiMode;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CurrencyCode;
use Creem\Resource\ProductsResource;
use Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('products resource gets creates and searches products', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('product.json')),
        MockResponse::make($this->responseFixture('product.json', ['id' => 'prod_fixture_enterprise', 'name' => 'Enterprise Fixture'])),
        MockResponse::make($this->responseFixture('product_page.json')),
    ]);
    $resource = new ProductsResource($this->connector($mockClient));

    $product = $resource->get('prod_fixture_catalog');

    expect($product->id)->toBe('prod_fixture_catalog')
        ->and($product->mode)->toBe(ApiMode::Test)
        ->and($product->currency)->toBe(CurrencyCode::USD)
        ->and($product->billingPeriod)->toBe(BillingPeriod::EveryMonth)
        ->and($product->createdAt?->format(DATE_ATOM))->toBe('2026-03-07T06:35:41+00:00')
        ->and($product->imageUrl)->toBeNull()
        ->and($product->features)->toBe([])
        ->and($product->defaultSuccessUrl)->toBeNull();
    $this->assertRequest($mockClient, Method::GET, '/v1/products', ['product_id' => 'prod_fixture_catalog']);

    $created = $resource->create(
        new CreateProductRequest('Enterprise', 4900, CurrencyCode::USD, BillingType::OneTime, description: 'Scale plan'),
        'idem-product-create',
    );

    expect($created->id)->toBe('prod_fixture_enterprise');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/products',
        [],
        ['name' => 'Enterprise', 'description' => 'Scale plan', 'price' => 4900, 'currency' => 'USD', 'billing_type' => 'onetime', 'custom_fields' => []],
        ['Idempotency-Key' => 'idem-product-create'],
    );

    $page = $resource->search(new SearchProductsRequest(1, 50));

    expect($page->count())->toBe(1)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(1)
        ->and($page->pagination?->nextPage)->toBe(2)
        ->and($page->get(0))->toBeInstanceOf(Product::class)
        ->and($page->get(0)?->id)->toBe('prod_fixture_catalog')
        ->and($page->get(0)?->currency)->toBe(CurrencyCode::USD);
    $this->assertRequest($mockClient, Method::GET, '/v1/products/search', ['page_number' => '1', 'page_size' => '50']);
});

test('products resource omits query parameters when search request is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('product_page.json')),
    ]);
    $resource = new ProductsResource($this->connector($mockClient));

    $page = $resource->search();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Product::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/products/search');
});
