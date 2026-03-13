<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Client;
use Creem\ClientFactory;
use Creem\CredentialProfile;
use Creem\CredentialProfiles;
use Creem\Enum\Environment;

test('client factory caches clients per profile and keeps configs distinct', function (): void {
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'sk_test_default_1234',
            environment: Environment::Test,
        ),
        'cashier' => new CredentialProfile(
            apiKey: 'sk_test_cashier_5678',
            environment: Environment::Production,
        ),
    ]);
    $factory = new ClientFactory($profiles);

    $defaultClient = $factory->forProfile('default');
    $cashierClient = $factory->forProfile('cashier');

    expect($factory->profiles())->toBe($profiles)
        ->and($defaultClient)->toBeInstanceOf(Client::class)
        ->and($cashierClient)->toBeInstanceOf(Client::class)
        ->and($defaultClient)->toBe($factory->forProfile('default'))
        ->and($cashierClient)->toBe($factory->forProfile('cashier'))
        ->and($defaultClient)->not->toBe($cashierClient)
        ->and($defaultClient->config()->apiKey())->toBe('sk_test_default_1234')
        ->and($defaultClient->config()->environment())->toBe(Environment::Test)
        ->and($cashierClient->config()->apiKey())->toBe('sk_test_cashier_5678')
        ->and($cashierClient->config()->environment())->toBe(Environment::Production);
});
