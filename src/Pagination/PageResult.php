<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Pagination;

final class PageResult
{
    /**
     * @param array<int, mixed> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize,
    ) {
    }

    /**
     * @return array{items:array<int,mixed>,total:int,page:int,pageSize:int}
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'page' => $this->page,
            'pageSize' => $this->pageSize,
        ];
    }
}
