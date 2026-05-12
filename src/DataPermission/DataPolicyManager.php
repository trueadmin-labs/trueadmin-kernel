<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\Database\Query\Builder as QueryBuilder;
use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Context\Actor;
use TrueAdmin\Kernel\Context\ActorContext;
use TrueAdmin\Kernel\Exception\BusinessException;

class DataPolicyManager
{
    /** @var array<string, DataPolicyStrategyInterface> */
    private array $strategies = [];

    public function __construct(
        private readonly DataPolicyProviderInterface $provider,
        private readonly DataPolicyRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed>|DataPolicyTarget $target
     */
    public function apply(ModelBuilder|QueryBuilder $query, string $resource, array|DataPolicyTarget $target = []): void
    {
        $this->registry->assertRegisteredResource($resource);

        $actor = ActorContext::principal();
        if (! $actor instanceof Actor || $actor->type === 'system') {
            return;
        }

        $allowRules = $this->allowRules($actor, $resource);
        if ($allowRules === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($this->hasAllScope($allowRules)) {
            return;
        }

        $target = DataPolicyTarget::make($target);
        $query->where(function ($group) use ($allowRules, $actor, $target): void {
            foreach ($allowRules as $index => $rule) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $group->{$method}(function ($subQuery) use ($actor, $rule, $target): void {
                    $this->strategy($rule->strategy)->apply($subQuery, $actor, $rule, $target);
                });
            }
        });
    }

    /**
     * @param array<string, mixed>|DataPolicyTarget $target
     */
    public function allows(ModelBuilder|QueryBuilder $query, string $resource, array|DataPolicyTarget $target = []): bool
    {
        $this->apply($query, $resource, $target);

        return (bool) $query->exists();
    }

    /**
     * @param array<string, mixed>|DataPolicyTarget $target
     */
    public function assertAllows(ModelBuilder|QueryBuilder $query, string $resource, array|DataPolicyTarget $target = []): void
    {
        if ($this->allows($query, $resource, $target)) {
            return;
        }

        throw new BusinessException(ErrorCode::FORBIDDEN, 403, [
            'reason' => 'data_policy_denied',
            'resource' => $resource,
        ]);
    }

    /**
     * @param list<int|string> $ids
     * @param array<string, mixed>|DataPolicyTarget $target
     */
    public function assertAllowsAll(ModelBuilder|QueryBuilder $query, string $resource, array $ids, string $idColumn = 'id', array|DataPolicyTarget $target = []): void
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int|string $id): bool => (string) $id !== '')));
        if ($ids === []) {
            return;
        }

        $this->apply($query, $resource, $target);
        $allowedCount = (int) $query->whereIn($idColumn, $ids)->distinct()->count($idColumn);
        if ($allowedCount === count($ids)) {
            return;
        }

        throw new BusinessException(ErrorCode::FORBIDDEN, 403, [
            'reason' => 'data_policy_denied',
            'resource' => $resource,
        ]);
    }

    /**
     * @return list<DataPolicyRule>
     */
    protected function allowRules(Actor $actor, string $resource): array
    {
        $rules = array_values(array_filter(
            $this->provider->policiesFor($actor, $resource),
            static fn (DataPolicyRule $rule): bool => $rule->matches($resource),
        ));

        $rules = array_values(array_filter($rules, static fn (DataPolicyRule $rule): bool => $rule->isAllow()));
        foreach ($rules as $rule) {
            $this->registry->assertRule($rule, $resource);
        }

        return $rules;
    }

    /**
     * @param list<DataPolicyRule> $rules
     */
    protected function hasAllScope(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule->isAllScope()) {
                return true;
            }
        }

        return false;
    }

    protected function strategy(string $key): DataPolicyStrategyInterface
    {
        return $this->strategies[$key] ??= $this->registry->strategy($key);
    }
}
