<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Enum\SubscriptionCancellationAction;
use Creem\Enum\SubscriptionCancellationMode;
use Creem\Internal\Serialization\RequestValueNormalizer;

final class CancelSubscriptionRequest
{
    public function __construct(
        public readonly ?SubscriptionCancellationMode $mode = null,
        public readonly ?SubscriptionCancellationAction $onExecute = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'mode' => $this->mode,
            'onExecute' => $this->onExecute,
        ]);
    }
}
