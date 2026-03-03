<?php

declare(strict_types=1);

namespace Creem;

use Creem\Internal\Http\UserAgent;
use InvalidArgumentException;

final class Config
{
    private readonly string $apiKey;

    private readonly Environment $environment;

    private readonly ?string $baseUrl;

    private readonly ?float $timeout;

    private readonly ?string $userAgentSuffix;

    public function __construct(
        string $apiKey,
        Environment $environment = Environment::Production,
        ?string $baseUrl = null,
        int|float|null $timeout = null,
        ?string $userAgentSuffix = null,
    ) {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new InvalidArgumentException('The Creem API key cannot be empty.');
        }

        if ($timeout !== null && $timeout <= 0) {
            throw new InvalidArgumentException('The Creem request timeout must be greater than zero.');
        }

        $normalizedBaseUrl = $baseUrl === null ? null : rtrim(trim($baseUrl), '/');

        if ($normalizedBaseUrl === '') {
            throw new InvalidArgumentException('The Creem base URL override cannot be blank.');
        }

        $normalizedSuffix = $userAgentSuffix === null ? null : trim($userAgentSuffix);

        if ($normalizedSuffix === '') {
            $normalizedSuffix = null;
        }

        $this->apiKey = $apiKey;
        $this->environment = $environment;
        $this->baseUrl = $normalizedBaseUrl;
        $this->timeout = $timeout === null ? null : (float) $timeout;
        $this->userAgentSuffix = $normalizedSuffix;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function timeout(): ?float
    {
        return $this->timeout;
    }

    public function userAgentSuffix(): ?string
    {
        return $this->userAgentSuffix;
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl ?? $this->environment->baseUrl();
    }

    public function userAgent(): string
    {
        return UserAgent::forConfig($this);
    }
}
