<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Discount\CreateDiscountRequest;
use Creem\Dto\Discount\Discount;
use Creem\Enum\CurrencyCode;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountStatus;
use Creem\Enum\DiscountType;
use Creem\Resource\DiscountsResource;
use Creem\Tests\IntegrationTestCase;
use InvalidArgumentException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('discounts resource retrieves creates and deletes discounts', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('discount.json')),
        MockResponse::make($this->responseFixture('discount.json', ['code' => 'WELCOME10'])),
        MockResponse::make($this->responseFixture('discount.json', ['id' => 'disc_456'])),
        MockResponse::make($this->responseFixture('discount.json', ['status' => 'expired'])),
    ]);
    $resource = new DiscountsResource($this->connector($mockClient));

    $discount = $resource->get('disc_123');

    expect($discount->id)->toBe('disc_123')
        ->and($discount->status)->toBe(DiscountStatus::Active);
    $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_id' => 'disc_123']);

    $byCode = $resource->getByCode('WELCOME10');

    expect($byCode->code)->toBe('WELCOME10');
    $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_code' => 'WELCOME10']);

    $created = $resource->create(
        new CreateDiscountRequest(
            'Launch',
            DiscountType::Fixed,
            DiscountDuration::Once,
            ['prod_123'],
            amount: 1000,
            currency: CurrencyCode::USD,
        ),
        'idem-discount-create',
    );

    expect($created->id)->toBe('disc_456');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/discounts',
        [],
        [
            'name' => 'Launch',
            'type' => 'fixed',
            'amount' => 1000,
            'currency' => 'USD',
            'duration' => 'once',
            'applies_to_products' => ['prod_123'],
        ],
        ['Idempotency-Key' => 'idem-discount-create'],
    );

    $deleted = $resource->delete('disc_123', 'idem-discount-delete');

    expect($deleted->status)->toBe(DiscountStatus::Expired);
    $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/disc_123/delete', [], null, ['Idempotency-Key' => 'idem-discount-delete']);
});

test('discounts resource normalizes delete identifiers before endpoint resolution', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('discount.json', ['status' => 'expired'])),
    ]);
    $resource = new DiscountsResource($this->connector($mockClient));

    $resource->delete('  disc_123  ');
    $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/disc_123/delete');
});

foreach (invalidDiscountDeleteIdentifiers() as $dataset => [$identifier, $message]) {
    test("discounts resource rejects invalid delete identifiers ({$dataset})", function () use ($identifier, $message): void {
        /** @var IntegrationTestCase $this */
        $resource = new DiscountsResource($this->connector(new MockClient));

        expect(static fn (): Discount => $resource->delete($identifier))
            ->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: string, 1: string}>
 */
function invalidDiscountDeleteIdentifiers(): array
{
    return [
        'path traversal' => [
            'disc_123/delete',
            'The discount ID cannot contain reserved URI characters or control characters.',
        ],
        'single dot segment' => [
            '.',
            'The discount ID cannot be "." or "..".',
        ],
        'double dot segment' => [
            '..',
            'The discount ID cannot be "." or "..".',
        ],
    ];
}
