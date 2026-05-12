<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

final readonly class CrudQueryApplierOptions
{
    /**
     * @param list<string> $keywordFields
     * @param array<string, bool|string|list<string>> $filterable
     * @param list<string>|'*' $defaultFilterOps
     * @param array<string, string> $filterColumns
     * @param list<string> $sortable
     * @param array<string, string> $sortColumns
     * @param array<string, string> $defaultSort
     */
    public function __construct(
        public array $keywordFields = [],
        public array $filterable = [],
        public array|string $defaultFilterOps = ['eq', 'in'],
        public array $filterColumns = [],
        public array $sortable = [],
        public array $sortColumns = [],
        public array $defaultSort = [],
    ) {
    }
}
