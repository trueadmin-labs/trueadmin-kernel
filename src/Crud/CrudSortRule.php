<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

final class CrudSortRule
{
    public function __construct(
        public readonly string $field,
        public readonly CrudSortOrder $order,
        public readonly ?string $nulls = null,
    ) {
    }

    public static function asc(string $field): self
    {
        return new self($field, CrudSortOrder::Asc);
    }

    public static function desc(string $field): self
    {
        return new self($field, CrudSortOrder::Desc);
    }
}
