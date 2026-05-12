<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

use TrueAdmin\Kernel\Http\Request\FormRequest;

class CrudQueryRequest extends FormRequest
{
    protected const FIELD_PATTERN = '/^[A-Za-z0-9_.]+$/';

    /**
     * @var list<string>
     */
    protected const ROOT_KEYS = ['page', 'pageSize', 'keyword', 'filters', 'sorts', 'params'];

    protected int $defaultPageSize = 20;

    protected int $maxPageSize = 100;

    protected int $maxFilterConditions = 20;

    protected int $maxSortRules = 5;

    public function rules(): array
    {
        return [
            '__unsupportedCrudKeys' => ['missing'],
            '__invalidCrudParamKeys' => ['missing'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:' . $this->maxPageSize],
            'keyword' => ['sometimes', 'string', 'max:100'],
            'filters' => ['sometimes', 'array', 'max:' . $this->maxFilterConditions],
            'filters.*.field' => ['required_with:filters', 'string', 'max:64', 'regex:' . $this->fieldPattern()],
            'filters.*.op' => ['required_with:filters', 'string', 'in:' . implode(',', $this->operatorValues())],
            'filters.*.value' => ['sometimes'],
            'sorts' => ['sometimes', 'array', 'max:' . $this->maxSortRules],
            'sorts.*.field' => ['required_with:sorts', 'string', 'max:64', 'regex:' . $this->fieldPattern()],
            'sorts.*.order' => ['required_with:sorts', 'string', 'in:asc,desc'],
            'sorts.*.nulls' => ['sometimes', 'string', 'in:first,last'],
            'params' => ['sometimes', 'array'],
        ];
    }

    protected function validationData(): array
    {
        $data = parent::validationData();

        $unsupportedKeys = array_values(array_diff(array_keys($data), $this->rootKeys()));
        if ($unsupportedKeys !== []) {
            $data['__unsupportedCrudKeys'] = $unsupportedKeys;
        }

        $invalidParamKeys = [];
        if (isset($data['params']) && is_array($data['params'])) {
            foreach (array_keys($data['params']) as $field) {
                if (! is_string($field) || ! $this->isAllowedField($field)) {
                    $invalidParamKeys[] = $field;
                }
            }
        }
        if ($invalidParamKeys !== []) {
            $data['__invalidCrudParamKeys'] = $invalidParamKeys;
        }

        return $data;
    }

    protected function normalize(array $data): array
    {
        return [
            'page' => (int) ($data['page'] ?? 1),
            'pageSize' => (int) ($data['pageSize'] ?? $this->defaultPageSize),
            'keyword' => trim((string) ($data['keyword'] ?? '')),
            'filters' => $this->decodeFilters($data['filters'] ?? []),
            'sorts' => $this->decodeSorts($data['sorts'] ?? []),
            'params' => $this->decodeParams($data['params'] ?? []),
        ];
    }

    public function crudQuery(): CrudQuery
    {
        $validated = $this->validated();

        return $this->makeCrudQuery(
            page: $validated['page'],
            pageSize: $validated['pageSize'],
            keyword: $validated['keyword'],
            filters: $validated['filters'],
            sorts: $validated['sorts'],
            params: $validated['params'],
        );
    }

    /**
     * @return list<CrudFilterCondition>
     */
    protected function decodeFilters(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $conditions = [];
        foreach (array_slice(array_values($value), 0, $this->maxFilterConditions) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $field = trim((string) ($item['field'] ?? ''));
            $operator = $this->decodeOperator($item['op'] ?? '');
            if (! $this->isAllowedField($field) || $operator === null) {
                continue;
            }

            $value = $this->normalizeValue($item['value'] ?? null);
            if (! in_array($operator, [CrudOperator::IsNull, CrudOperator::NotNull], true) && $this->isEmptyValue($value)) {
                continue;
            }

            $conditions[] = $this->makeFilterCondition($field, $operator, $value);
        }

        return $conditions;
    }

    /**
     * @return list<CrudSortRule>
     */
    protected function decodeSorts(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $sorts = [];
        foreach (array_slice(array_values($value), 0, $this->maxSortRules) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $field = trim((string) ($item['field'] ?? ''));
            $order = $this->decodeSortOrder($item['order'] ?? '');
            $nulls = isset($item['nulls']) ? strtolower(trim((string) $item['nulls'])) : null;
            if (! $this->isAllowedField($field) || $order === null) {
                continue;
            }
            if (! in_array($nulls, [null, 'first', 'last'], true)) {
                $nulls = null;
            }

            $sorts[] = $this->makeSortRule($field, $order, $nulls);
        }

        return $sorts;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeParams(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $params = [];
        foreach ($value as $field => $fieldValue) {
            if (! is_string($field) || ! $this->isAllowedField($field) || $this->isEmptyValue($fieldValue)) {
                continue;
            }
            $params[$field] = $this->normalizeValue($fieldValue);
        }

        return $params;
    }

    protected function isAllowedField(string $field): bool
    {
        return preg_match($this->fieldPattern(), $field) === 1;
    }

    /**
     * @return list<string>
     */
    protected function operatorValues(): array
    {
        return array_map(
            static fn (CrudOperator $operator): string => $operator->value,
            CrudOperator::cases(),
        );
    }

    protected function isEmptyValue(mixed $value): bool
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || $value === 'all') {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        return $this->normalizeValue($value) === [];
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? trim($value) : $value;
        }

        return array_values(array_filter(
            array_map(static function (mixed $item): mixed {
                if (! is_scalar($item)) {
                    return null;
                }

                return is_string($item) ? trim($item) : $item;
            }, $value),
            static fn (mixed $item): bool => $item !== null && $item !== '' && $item !== 'all',
        ));
    }

    /**
     * @return list<string>
     */
    protected function rootKeys(): array
    {
        return static::ROOT_KEYS;
    }

    protected function fieldPattern(): string
    {
        return static::FIELD_PATTERN;
    }

    protected function decodeOperator(mixed $value): ?CrudOperator
    {
        return CrudOperator::tryFrom(strtolower(trim((string) $value)));
    }

    protected function decodeSortOrder(mixed $value): ?CrudSortOrder
    {
        return CrudSortOrder::tryFrom(strtolower(trim((string) $value)));
    }

    /**
     * @param list<CrudFilterCondition> $filters
     * @param list<CrudSortRule> $sorts
     * @param array<string, mixed> $params
     */
    protected function makeCrudQuery(
        int $page,
        int $pageSize,
        string $keyword,
        array $filters,
        array $sorts,
        array $params,
    ): CrudQuery {
        return new CrudQuery($page, $pageSize, $keyword, $filters, $sorts, $params);
    }

    protected function makeFilterCondition(string $field, CrudOperator $operator, mixed $value): CrudFilterCondition
    {
        return new CrudFilterCondition($field, $operator, $value);
    }

    protected function makeSortRule(string $field, CrudSortOrder $order, ?string $nulls): CrudSortRule
    {
        return new CrudSortRule($field, $order, $nulls);
    }
}
