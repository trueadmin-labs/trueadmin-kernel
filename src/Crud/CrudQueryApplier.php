<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

use Hyperf\Database\Model\Builder;
use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Exception\BusinessException;

class CrudQueryApplier
{
    public function applyKeyword(Builder $query, CrudQuery $crudQuery, CrudQueryApplierOptions $options): void
    {
        $fields = $options->keywordFields;
        if ($crudQuery->keyword === '' || $fields === []) {
            return;
        }

        $keyword = '%' . $crudQuery->keyword . '%';
        $query->where(static function ($query) use ($fields, $keyword): void {
            foreach ($fields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($field, 'like', $keyword);
            }
        });
    }

    /**
     * @param null|callable(Builder, string, CrudOperator, mixed, string): void $conditionApplier
     */
    public function applyFilters(
        Builder $query,
        CrudQuery $crudQuery,
        CrudQueryApplierOptions $options,
        ?callable $conditionApplier = null,
    ): void {
        foreach ($crudQuery->filters as $condition) {
            $field = $condition->field;
            $operator = $condition->op;
            $value = $condition->value;

            if ($value === null || $value === '' || $value === 'all') {
                if (! in_array($operator, [CrudOperator::IsNull, CrudOperator::NotNull], true)) {
                    continue;
                }
            }

            $this->assertFilterable($field, $operator, $options);
            $conditionApplier ??= $this->applyFilterCondition(...);
            $conditionApplier($query, $field, $operator, $value, $this->filterColumn($field, $options));
        }
    }

    /**
     * @param null|callable(Builder, CrudSortRule, string): void $sortRuleApplier
     * @param null|callable(Builder, string, string): void $defaultSortRuleApplier
     */
    public function applySort(
        Builder $query,
        CrudQuery $crudQuery,
        CrudQueryApplierOptions $options,
        ?callable $sortRuleApplier = null,
        ?callable $defaultSortRuleApplier = null,
    ): void {
        if ($crudQuery->sorts !== []) {
            foreach ($crudQuery->sorts as $sort) {
                $sortRuleApplier ??= $this->applySortRule(...);
                $sortRuleApplier($query, $sort, $this->assertSortable($sort, $options));
            }
            return;
        }

        foreach ($options->defaultSort as $field => $direction) {
            $defaultSortRuleApplier ??= function (Builder $query, string $field, string $direction) use ($options): void {
                $this->applyDefaultSortRule($query, $field, $direction, $options);
            };
            $defaultSortRuleApplier($query, (string) $field, $direction);
        }
    }

    public function applyFilterCondition(Builder $query, string $field, CrudOperator $operator, mixed $value, string $column): void
    {
        if ($operator === CrudOperator::IsNull) {
            $query->whereNull($column);
            return;
        }
        if ($operator === CrudOperator::NotNull) {
            $query->whereNotNull($column);
            return;
        }
        if ($operator === CrudOperator::Like) {
            $query->where($column, 'like', '%' . (string) $value . '%');
            return;
        }
        if ($operator === CrudOperator::In) {
            $values = is_array($value) ? $value : explode(',', (string) $value);
            $values = array_values(array_filter($values, static fn (mixed $item): bool => $item !== '' && $item !== null));
            if ($values !== []) {
                $query->whereIn($column, $values);
            }
            return;
        }
        if ($operator === CrudOperator::Between) {
            $values = is_array($value) ? array_values($value) : explode(',', (string) $value, 2);
            if (count($values) >= 2 && $values[0] !== '' && $values[1] !== '') {
                $query->whereBetween($column, [$values[0], $values[1]]);
            }
            return;
        }

        $query->where($column, $operator->sqlOperator(), $value);
    }

    public function applySortRule(Builder $query, CrudSortRule $sort, string $column): void
    {
        $query->orderBy($column, $sort->order->value);
    }

    public function applyDefaultSortRule(Builder $query, string $field, string $direction, CrudQueryApplierOptions $options): void
    {
        $query->orderBy(
            $this->sortColumn($field, $options),
            strtolower($direction) === 'desc' ? 'desc' : 'asc',
        );
    }

    public function assertFilterable(string $field, CrudOperator $operator, CrudQueryApplierOptions $options): void
    {
        if (! array_key_exists($field, $options->filterable)) {
            throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, [
                'field' => $field,
                'reason' => 'unsupported_filter_field',
            ]);
        }

        $allowed = $options->filterable[$field];
        if ($allowed === false) {
            throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, [
                'field' => $field,
                'reason' => 'unsupported_filter_field',
            ]);
        }

        if ($allowed === true) {
            $allowed = $options->defaultFilterOps;
        }
        if ($allowed === '*') {
            return;
        }

        if (is_string($allowed)) {
            $allowed = [$allowed];
        }

        if (! in_array($operator->value, $allowed, true)) {
            throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, [
                'field' => $field,
                'operator' => $operator->value,
                'reason' => 'unsupported_filter_operator',
            ]);
        }
    }

    public function assertSortable(CrudSortRule $sort, CrudQueryApplierOptions $options): string
    {
        if (! in_array($sort->field, $options->sortable, true)) {
            throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, [
                'field' => $sort->field,
                'reason' => 'unsupported_sort_field',
            ]);
        }

        return $this->sortColumn($sort->field, $options);
    }

    public function filterColumn(string $field, CrudQueryApplierOptions $options): string
    {
        return $options->filterColumns[$field] ?? $field;
    }

    public function sortColumn(string $field, CrudQueryApplierOptions $options): string
    {
        return $options->sortColumns[$field] ?? $field;
    }
}
