<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\License\ActivateLicenseRequest;
use Creem\Dto\License\DeactivateLicenseRequest;
use Creem\Dto\License\LicenseInstance;
use Creem\Dto\License\ValidateLicenseRequest;
use Creem\Enum\LicenseStatus;
use Creem\Resource\LicensesResource;
use Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('licenses resource activates deactivates and validates licenses', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('license.json')),
        MockResponse::make($this->responseFixture('license.json', ['status' => 'inactive'])),
        MockResponse::make($this->responseFixture('license.json', ['activation' => 1])),
    ]);
    $resource = new LicensesResource($this->connector($mockClient));

    $activated = $resource->activate(new ActivateLicenseRequest('lic_key', 'macbook'), 'idem-license-activate');

    expect($activated->id)->toBe('lic_123')
        ->and($activated->status)->toBe(LicenseStatus::Active)
        ->and($activated->instance)->toBeInstanceOf(LicenseInstance::class)
        ->and($activated->instance?->id)->toBe('ins_123');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/activate',
        [],
        ['key' => 'lic_key', 'instance_name' => 'macbook'],
        ['Idempotency-Key' => 'idem-license-activate'],
    );

    $deactivated = $resource->deactivate(new DeactivateLicenseRequest('lic_key', 'ins_123'), 'idem-license-deactivate');

    expect($deactivated->status)->toBe(LicenseStatus::Inactive);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/deactivate',
        [],
        ['key' => 'lic_key', 'instance_id' => 'ins_123'],
        ['Idempotency-Key' => 'idem-license-deactivate'],
    );

    $validated = $resource->validate(new ValidateLicenseRequest('lic_key', 'ins_123'), 'idem-license-validate');

    expect($validated->activation)->toBe(1);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/validate',
        [],
        ['key' => 'lic_key', 'instance_id' => 'ins_123'],
        ['Idempotency-Key' => 'idem-license-validate'],
    );
});
