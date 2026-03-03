<?php

declare(strict_types=1);

namespace Creem\Dto\Product;

use Creem\Internal\Serialization\RequestValueNormalizer;

final class SearchProductsRequest
{
    public function __construct(
        public readonly ?int $pageNumber = null,
        public readonly ?int $pageSize = null,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toQuery(): array
    {
        /** @var array<string, int> */
        return RequestValueNormalizer::query([
            'page_number' => $this->pageNumber,
            'page_size' => $this->pageSize,
        ]);
    }
}
