<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\Database\Query\Builder as QueryBuilder;
use TrueAdmin\Kernel\Context\Actor;

interface DataPolicyStrategyInterface
{
    public function key(): string;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;

    public function apply(ModelBuilder|QueryBuilder $query, Actor $actor, DataPolicyRule $rule, DataPolicyTarget $target): void;

    public function contains(DataPolicyRule $parent, DataPolicyRule $child): bool;
}
