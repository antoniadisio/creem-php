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

    $activated = $resource->activate(new ActivateLicenseRequest('license_fixture_key_primary', 'macbook-fixture'), 'idem-license-activate');

    expect($activated->id)->toBe('lic_fixture_primary')
        ->and($activated->status)->toBe(LicenseStatus::Active)
        ->and($activated->instance)->toBeInstanceOf(LicenseInstance::class)
        ->and($activated->instance?->id)->toBe('ins_fixture_macbook');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/activate',
        [],
        ['key' => 'license_fixture_key_primary', 'instance_name' => 'macbook-fixture'],
        ['Idempotency-Key' => 'idem-license-activate'],
    );

    $deactivated = $resource->deactivate(new DeactivateLicenseRequest('license_fixture_key_primary', 'ins_fixture_macbook'), 'idem-license-deactivate');

    expect($deactivated->status)->toBe(LicenseStatus::Inactive);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/deactivate',
        [],
        ['key' => 'license_fixture_key_primary', 'instance_id' => 'ins_fixture_macbook'],
        ['Idempotency-Key' => 'idem-license-deactivate'],
    );

    $validated = $resource->validate(new ValidateLicenseRequest('license_fixture_key_primary', 'ins_fixture_macbook'), 'idem-license-validate');

    expect($validated->activation)->toBe(1);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/validate',
        [],
        ['key' => 'license_fixture_key_primary', 'instance_id' => 'ins_fixture_macbook'],
        ['Idempotency-Key' => 'idem-license-validate'],
    );
});
