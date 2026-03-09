<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Creem\Dto\Customer\Customer;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Enum\ApiMode;
use Creem\Resource\CustomersResource;
use Creem\Tests\IntegrationTestCase;
use DateTimeImmutable;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('customers resource lists retrieves and finds customers by email', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('customer_page.json')),
        MockResponse::make($this->responseFixture('customer.json')),
        MockResponse::make($this->responseFixture('customer.json', ['id' => 'cus_email', 'email' => 'billing@example.com'])),
        MockResponse::make($this->responseFixture('customer_links.json')),
    ]);
    $resource = new CustomersResource($this->connector($mockClient));

    $page = $resource->list(new ListCustomersRequest(1, 20));

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Customer::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers/list', ['page_number' => '1', 'page_size' => '20']);

    $customer = $resource->get('cus_123');

    expect($customer->id)->toBe('cus_123')
        ->and($customer->mode)->toBe(ApiMode::Test)
        ->and($customer->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['customer_id' => 'cus_123']);

    $customerByEmail = $resource->findByEmail('billing@example.com');

    expect($customerByEmail->email)->toBe('billing@example.com');
    $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['email' => 'billing@example.com']);

    $links = $resource->createBillingPortalLink(new CreateCustomerBillingPortalLinkRequest('cus_123'), 'idem-customer-links');

    expect($links->customerPortalLink)->toBe('https://billing.creem.io/session');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/customers/billing',
        [],
        ['customer_id' => 'cus_123'],
        ['Idempotency-Key' => 'idem-customer-links'],
    );
});

test('customers resource omits query parameters when list request is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('customer_page.json')),
    ]);
    $resource = new CustomersResource($this->connector($mockClient));

    $page = $resource->list();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Customer::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers/list');
});
