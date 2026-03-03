<?php

declare(strict_types=1);

namespace Creem;

use Creem\Internal\Http\CreemConnector;
use Creem\Resource\CheckoutsResource;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;

final class Client
{
    private readonly CreemConnector $connector;

    private ?ProductsResource $products = null;

    private ?CustomersResource $customers = null;

    private ?SubscriptionsResource $subscriptions = null;

    private ?CheckoutsResource $checkouts = null;

    private ?LicensesResource $licenses = null;

    private ?DiscountsResource $discounts = null;

    private ?TransactionsResource $transactions = null;

    private ?StatsResource $stats = null;

    public function __construct(
        private readonly Config $config,
    ) {
        $this->connector = new CreemConnector($config);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function products(): ProductsResource
    {
        return $this->products ??= new ProductsResource($this->connector);
    }

    public function customers(): CustomersResource
    {
        return $this->customers ??= new CustomersResource($this->connector);
    }

    public function subscriptions(): SubscriptionsResource
    {
        return $this->subscriptions ??= new SubscriptionsResource($this->connector);
    }

    public function checkouts(): CheckoutsResource
    {
        return $this->checkouts ??= new CheckoutsResource($this->connector);
    }

    public function licenses(): LicensesResource
    {
        return $this->licenses ??= new LicensesResource($this->connector);
    }

    public function discounts(): DiscountsResource
    {
        return $this->discounts ??= new DiscountsResource($this->connector);
    }

    public function transactions(): TransactionsResource
    {
        return $this->transactions ??= new TransactionsResource($this->connector);
    }

    public function stats(): StatsResource
    {
        return $this->stats ??= new StatsResource($this->connector);
    }
}
