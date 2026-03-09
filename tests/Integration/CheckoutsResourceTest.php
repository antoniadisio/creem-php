<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Dto\Common\CustomField;
use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\Order;
use Creem\Dto\Common\ProductFeature;
use Creem\Enum\CheckoutStatus;
use Creem\Enum\CustomFieldType;
use Creem\Enum\OrderStatus;
use Creem\Enum\ProductFeatureType;
use Creem\Resource\CheckoutsResource;
use Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('checkouts resource gets and creates checkouts', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('checkout.json')),
        MockResponse::make($this->responseFixture('checkout.json', ['id' => 'chk_fixture_created'])),
    ]);
    $resource = new CheckoutsResource($this->connector($mockClient));

    $checkout = $resource->get('chk_fixture_pending');

    expect($checkout->id)->toBe('chk_fixture_pending')
        ->and($checkout->status)->toBe(CheckoutStatus::Pending)
        ->and($checkout->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($checkout->product?->isExpanded())->toBeTrue()
        ->and($checkout->order)->toBeInstanceOf(Order::class)
        ->and($checkout->order?->status)->toBe(OrderStatus::Paid)
        ->and($checkout->customFields[0] ?? null)->toBeInstanceOf(CustomField::class)
        ->and($checkout->customFields[0]->type ?? null)->toBe(CustomFieldType::Text)
        ->and($checkout->feature[0] ?? null)->toBeInstanceOf(ProductFeature::class)
        ->and($checkout->feature[0]->type ?? null)->toBe(ProductFeatureType::File)
        ->and($checkout->metadata)->toBeArray()
        ->and($checkout->metadata['source'] ?? null)->toBe('sdk-fixture')
        ->and($checkout->metadata['attempt'] ?? null)->toBeInt();
    $this->assertRequest($mockClient, Method::GET, '/v1/checkouts', ['checkout_id' => 'chk_fixture_pending']);

    $created = $resource->create(
        new CreateCheckoutRequest('prod_fixture_starter', requestId: 'req_fixture_checkout_create', units: 2, successUrl: 'https://merchant.example/checkout/success'),
        'idem-checkout-create',
    );

    expect($created->id)->toBe('chk_fixture_created');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/checkouts',
        [],
        ['request_id' => 'req_fixture_checkout_create', 'product_id' => 'prod_fixture_starter', 'units' => 2, 'custom_fields' => [], 'success_url' => 'https://merchant.example/checkout/success'],
        ['Idempotency-Key' => 'idem-checkout-create'],
    );
});
