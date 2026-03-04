<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Checkout\Checkout;
use Creem\Dto\Common\CustomField;
use Creem\Dto\Common\ProductFeature;
use Creem\Dto\Discount\Discount;
use Creem\Dto\Subscription\Subscription;
use Creem\Enum\CustomFieldType;
use Creem\Enum\ProductFeatureType;
use Creem\Exception\HydrationException;
use Creem\Tests\TestCase;

test('custom field hydration supports checkbox variants', function (): void {
    $field = CustomField::fromPayload([
        'type' => 'checkbox',
        'key' => 'termsAccepted',
        'label' => 'Terms Accepted',
        'optional' => true,
        'checkbox' => [
            'label' => 'I agree to the terms',
            'value' => true,
        ],
    ]);

    expect($field->type)->toBe(CustomFieldType::Checkbox)
        ->and($field->key)->toBe('termsAccepted')
        ->and($field->label)->toBe('Terms Accepted')
        ->and($field->optional)->toBeTrue()
        ->and($field->text)->toBeNull()
        ->and($field->checkbox?->label)->toBe('I agree to the terms')
        ->and($field->checkbox?->value)->toBeTrue();
});

test('product feature hydration supports license key variants', function (): void {
    /** @var TestCase $this */
    $feature = ProductFeature::fromPayload([
        'id' => 'feat_1',
        'description' => 'License access',
        'type' => 'licenseKey',
        'license_key' => $this->fixture('license.json'),
    ]);

    expect($feature->type)->toBe(ProductFeatureType::LicenseKey)
        ->and($feature->licenseKey?->id)->toBe('lic_123')
        ->and($feature->license)->toBeNull();
});

test('product feature hydration supports license variants', function (): void {
    /** @var TestCase $this */
    $feature = ProductFeature::fromPayload([
        'id' => 'feat_2',
        'description' => 'License object',
        'license' => $this->fixture('license.json'),
    ]);

    expect($feature->type)->toBeNull()
        ->and($feature->license?->id)->toBe('lic_123')
        ->and($feature->licenseKey)->toBeNull();
});

test('checkout hydration rejects malformed custom field items', function (): void {
    /** @var TestCase $this */
    $payload = $this->fixture('checkout.json');
    $payload['custom_fields'] = ['invalid'];

    expect(static fn (): Checkout => Checkout::fromPayload($payload))
        ->toThrow(HydrationException::class, 'Hydration failed for Checkout.custom_fields: expected object, got string.');
});

test('checkout hydration rejects malformed feature items', function (): void {
    /** @var TestCase $this */
    $payload = $this->fixture('checkout.json');
    $payload['feature'] = ['invalid'];

    expect(static fn (): Checkout => Checkout::fromPayload($payload))
        ->toThrow(HydrationException::class, 'Hydration failed for Checkout.feature: expected object, got string.');
});

test('subscription hydration rejects malformed item payloads', function (): void {
    /** @var TestCase $this */
    $payload = $this->fixture('subscription.json');
    $payload['items'] = ['invalid'];

    expect(static fn (): Subscription => Subscription::fromPayload($payload))
        ->toThrow(HydrationException::class, 'Hydration failed for Subscription.items: expected object, got string.');
});

test('discount hydration rejects malformed applies to products values', function (): void {
    /** @var TestCase $this */
    $payload = $this->fixture('discount.json');
    $payload['applies_to_products'] = [123];

    expect(static fn (): Discount => Discount::fromPayload($payload))
        ->toThrow(HydrationException::class, 'Hydration failed for Discount.applies_to_products: expected string, got int.');
});
