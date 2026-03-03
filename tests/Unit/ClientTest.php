<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Client;
use Creem\Config;
use Creem\Resource\CheckoutsResource;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function test_client_exposes_stable_resource_accessors(): void
    {
        $client = new Client(new Config('sk_test_123'));

        self::assertInstanceOf(ProductsResource::class, $client->products());
        self::assertInstanceOf(CustomersResource::class, $client->customers());
        self::assertInstanceOf(SubscriptionsResource::class, $client->subscriptions());
        self::assertInstanceOf(CheckoutsResource::class, $client->checkouts());
        self::assertInstanceOf(LicensesResource::class, $client->licenses());
        self::assertInstanceOf(DiscountsResource::class, $client->discounts());
        self::assertInstanceOf(TransactionsResource::class, $client->transactions());
        self::assertInstanceOf(StatsResource::class, $client->stats());
        self::assertSame($client->products(), $client->products());
    }

    public function test_client_retains_the_supplied_config(): void
    {
        $config = new Config('sk_test_123');
        $client = new Client($config);

        self::assertSame($config, $client->config());
    }
}
