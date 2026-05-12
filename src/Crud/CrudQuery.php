<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

final class CrudQuery
{
    /**
     * @param list<CrudFilterCondition> $filters
     * @param list<CrudSortRule> $sorts
     * @param array<string, mixed> $params
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly int $pageSize = 20,
        public readonly string $keyword = '',
        public readonly array $filters = [],
        public readonly array $sorts = [],
        public readonly array $params = [],
    ) {
    }

    public function hasParam(string $field): bool
    {
        return array_key_exists($field, $this->params);
    }

    public function param(string $field, mixed $default = null): mixed
    {
        return $this->params[$field] ?? $default;
    }

    public function hasFilter(string $field): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->field === $field) {
                return true;
            }
        }

        return false;
    }

    public function filter(string $field, mixed $default = null): mixed
    {
        foreach ($this->filters as $filter) {
            if ($filter->field === $field) {
                return $filter->value;
            }
        }

        return $default;
    }

    /**
     * @return list<CrudFilterCondition>
     */
    public function filtersFor(string $field): array
    {
        return array_values(array_filter(
            $this->filters,
            static fn (CrudFilterCondition $filter): bool => $filter->field === $field,
        ));
    }
}
