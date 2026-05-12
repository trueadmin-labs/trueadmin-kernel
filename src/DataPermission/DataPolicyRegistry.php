<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Exception\BusinessException;
use TrueAdmin\Kernel\Plugin\PluginRepository;

class DataPolicyRegistry
{
    /** @var null|array<string, array<string, mixed>> */
    private ?array $resources = null;

    /** @var null|array<string, array<string, mixed>> */
    private ?array $strategies = null;

    /** @var null|list<array<string, mixed>> */
    private ?array $resourceDefinitions = null;

    /** @var null|list<class-string> */
    private ?array $strategyClasses = null;

    /** @var array<string, DataPolicyStrategyInterface> */
    private array $strategyInstances = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigInterface $config,
        private readonly PluginRepository $plugins,
    ) {
    }

    /**
     * @return array{resources: list<array<string, mixed>>, strategies: list<array<string, mixed>>}
     */
    public function metadata(): array
    {
        return [
            'resources' => array_values($this->resources()),
            'strategies' => array_values($this->strategies()),
        ];
    }

    /**
     * @return list<DataPolicyRule>
     */
    public function allScopeRules(?int $roleId = null): array
    {
        $rules = [];
        foreach ($this->resources() as $resource) {
            foreach ($resource['strategies'] as $strategy) {
                $rules[] = new DataPolicyRule(
                    resource: $resource['key'],
                    strategy: $strategy,
                    scope: 'all',
                    roleId: $roleId,
                );
            }
        }

        return $rules;
    }

    public function assertRegisteredResource(string $resource): void
    {
        $resources = $this->resources();
        if (! isset($resources[$resource])) {
            throw new RuntimeException(sprintf('Data policy resource [%s] is not registered.', $resource));
        }
    }

    /**
     * @param array<string, mixed> $policy
     */
    public function assertPolicyInput(array $policy): void
    {
        $resource = (string) ($policy['resource'] ?? '');
        $strategy = (string) ($policy['strategy'] ?? '');
        $scope = (string) ($policy['scope'] ?? '');

        $resourceConfig = $this->resources()[$resource] ?? null;
        if ($resourceConfig === null) {
            $this->validationError('unsupported_data_policy_resource');
        }

        if (! in_array($strategy, $resourceConfig['strategies'], true)) {
            $this->validationError('unsupported_data_policy_strategy');
        }

        $strategyConfig = $this->strategies()[$strategy] ?? null;
        if ($strategyConfig === null) {
            $this->validationError('unsupported_data_policy_strategy');
        }

        $scopes = array_column($strategyConfig['scopes'], 'key');
        if (! in_array($scope, $scopes, true)) {
            $this->validationError('unsupported_data_policy_scope');
        }
    }

    /**
     * @param list<array<string, mixed>> $policies
     */
    public function assertUniquePolicies(array $policies): void
    {
        $seen = [];
        foreach ($policies as $policy) {
            $key = sprintf('%s:%s', (string) ($policy['resource'] ?? ''), (string) ($policy['strategy'] ?? ''));
            if (isset($seen[$key])) {
                $this->validationError('duplicate_data_policy');
            }
            $seen[$key] = true;
        }
    }

    public function assertRule(DataPolicyRule $rule, string $resource): void
    {
        if (! $rule->matches($resource)) {
            throw new RuntimeException(sprintf('Data policy rule [%s] does not match [%s].', $rule->resource, $resource));
        }

        $resourceConfig = $this->resources()[$rule->resource] ?? null;
        if ($resourceConfig === null) {
            throw new RuntimeException(sprintf('Data policy resource [%s] is not registered.', $rule->resource));
        }
        if (! in_array($rule->strategy, $resourceConfig['strategies'], true)) {
            throw new RuntimeException(sprintf('Data policy strategy [%s] is not allowed for resource [%s].', $rule->strategy, $rule->resource));
        }

        $strategy = $this->strategies()[$rule->strategy] ?? null;
        if ($strategy === null) {
            throw new RuntimeException(sprintf('Data policy strategy [%s] is not registered.', $rule->strategy));
        }
        if (! in_array($rule->scope, array_column($strategy['scopes'], 'key'), true)) {
            throw new RuntimeException(sprintf('Data policy scope [%s] is not registered for strategy [%s].', $rule->scope, $rule->strategy));
        }
    }

    public function strategy(string $key): DataPolicyStrategyInterface
    {
        if ($this->strategyInstances === []) {
            $this->loadStrategies();
        }

        if (! isset($this->strategyInstances[$key])) {
            throw new RuntimeException(sprintf('Data policy strategy [%s] is not registered.', $key));
        }

        return $this->strategyInstances[$key];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function resources(): array
    {
        if ($this->resources !== null) {
            return $this->resources;
        }

        $resources = [];
        foreach ($this->resourceDefinitions() as $resource) {
            if (! is_array($resource)) {
                continue;
            }
            $key = trim((string) ($resource['key'] ?? ''));
            if ($key === '') {
                throw new RuntimeException('Data policy resource key cannot be empty.');
            }
            if (isset($resources[$key])) {
                throw new RuntimeException(sprintf('Duplicate data policy resource [%s].', $key));
            }
            if (array_key_exists('actions', $resource)) {
                throw new RuntimeException(sprintf('Data policy resource [%s] must not define actions.', $key));
            }

            $strategies = array_values(array_unique(array_filter(array_map('strval', (array) ($resource['strategies'] ?? [])))));
            if ($strategies === []) {
                throw new RuntimeException(sprintf('Data policy resource [%s] must define strategies.', $key));
            }
            foreach ($strategies as $strategy) {
                if (! isset($this->strategies()[$strategy])) {
                    throw new RuntimeException(sprintf('Data policy resource [%s] references unregistered strategy [%s].', $key, $strategy));
                }
            }

            $resources[$key] = [
                'key' => $key,
                'label' => (string) ($resource['label'] ?? $key),
                'i18n' => (string) ($resource['i18n'] ?? ''),
                'strategies' => $strategies,
                'sort' => (int) ($resource['sort'] ?? 0),
            ];
        }

        uasort($resources, static fn (array $a, array $b): int => [$a['sort'], $a['key']] <=> [$b['sort'], $b['key']]);

        return $this->resources = $resources;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function strategies(): array
    {
        if ($this->strategies !== null) {
            return $this->strategies;
        }

        $this->loadStrategies();
        $strategies = [];
        foreach ($this->strategyInstances as $key => $strategy) {
            $metadata = $strategy->metadata();
            $scopes = $this->normalizeScopes($key, $metadata['scopes'] ?? []);
            $strategies[$key] = [
                'key' => $key,
                'label' => (string) ($metadata['label'] ?? $key),
                'i18n' => (string) ($metadata['i18n'] ?? ''),
                'scopes' => $scopes,
                'sort' => (int) ($metadata['sort'] ?? 0),
            ];
        }

        uasort($strategies, static fn (array $a, array $b): int => [$a['sort'], $a['key']] <=> [$b['sort'], $b['key']]);

        return $this->strategies = $strategies;
    }

    protected function loadStrategies(): void
    {
        if ($this->strategyInstances !== []) {
            return;
        }

        foreach ($this->strategyClasses() as $strategyClass) {
            $strategy = $this->container->get($strategyClass);
            if (! $strategy instanceof DataPolicyStrategyInterface) {
                throw new RuntimeException(sprintf('Data policy strategy [%s] must implement %s.', (string) $strategyClass, DataPolicyStrategyInterface::class));
            }
            $metadata = $strategy->metadata();
            $key = (string) ($metadata['key'] ?? $strategy->key());
            if ($key !== $strategy->key()) {
                throw new RuntimeException(sprintf('Data policy strategy metadata key [%s] must equal strategy key [%s].', $key, $strategy->key()));
            }
            if (isset($this->strategyInstances[$key])) {
                throw new RuntimeException(sprintf('Duplicate data policy strategy [%s].', $key));
            }
            $this->strategyInstances[$key] = $strategy;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function resourceDefinitions(): array
    {
        if ($this->resourceDefinitions !== null) {
            return $this->resourceDefinitions;
        }

        $resources = [];
        foreach ($this->dataPolicyDefinitions() as $definition) {
            foreach ($definition['resources'] as $resource) {
                $resources[] = $resource;
            }
        }

        return $this->resourceDefinitions = $resources;
    }

    /**
     * @return list<class-string>
     */
    protected function strategyClasses(): array
    {
        if ($this->strategyClasses !== null) {
            return $this->strategyClasses;
        }

        $classes = [];
        foreach ($this->dataPolicyDefinitions() as $definition) {
            foreach ($definition['strategies'] as $strategyClass) {
                if (! is_string($strategyClass) || $strategyClass === '') {
                    throw new RuntimeException('Data policy strategy class must be a non-empty string.');
                }

                $classes[] = $strategyClass;
            }
        }

        return $this->strategyClasses = array_values(array_unique($classes));
    }

    /**
     * @return list<array{strategies: list<class-string>, resources: list<array<string, mixed>>}>
     */
    protected function dataPolicyDefinitions(): array
    {
        $definitions = [];

        $definitions[] = $this->normalizeDataPolicyDefinition((array) $this->config->get('data_policy', []), 'config:data_policy');

        foreach ($this->dataPolicyResourceFiles() as $file) {
            $definition = require $file;
            if (! is_array($definition)) {
                throw new RuntimeException(sprintf('Data policy resource file [%s] must return an array.', $file));
            }

            $definitions[] = $this->normalizeDataPolicyDefinition($definition, $file);
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array{strategies: list<class-string>, resources: list<array<string, mixed>>}
     */
    protected function normalizeDataPolicyDefinition(array $definition, string $source): array
    {
        $strategies = $definition['strategies'] ?? [];
        $resources = $definition['resources'] ?? [];
        if (! is_array($strategies) || ! is_array($resources)) {
            throw new RuntimeException(sprintf('Data policy definition [%s] must define strategies and resources as arrays.', $source));
        }

        foreach ($resources as $resource) {
            if (! is_array($resource)) {
                throw new RuntimeException(sprintf('Data policy definition [%s] contains an invalid resource.', $source));
            }
        }

        return [
            'strategies' => array_values($strategies),
            'resources' => array_values($resources),
        ];
    }

    /**
     * @return list<string>
     */
    protected function dataPolicyResourceFiles(): array
    {
        $files = [
            ...(glob(BASE_PATH . '/app/Module/*/resources/data_policies.php') ?: []),
            ...$this->plugins->dataPolicyResourceFiles(),
        ];

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @param mixed $scopes
     * @return list<array<string, mixed>>
     */
    protected function normalizeScopes(string $strategy, mixed $scopes): array
    {
        $items = [];
        foreach ((array) $scopes as $scope) {
            if (is_string($scope)) {
                $key = trim($scope);
                if ($key === '') {
                    continue;
                }
                $items[] = ['key' => $key, 'label' => $key, 'i18n' => '', 'sort' => count($items)];
                continue;
            }
            if (! is_array($scope)) {
                continue;
            }
            $key = trim((string) ($scope['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $items[] = [
                'key' => $key,
                'label' => (string) ($scope['label'] ?? $key),
                'i18n' => (string) ($scope['i18n'] ?? ''),
                'sort' => (int) ($scope['sort'] ?? count($items)),
                'configSchema' => $this->normalizeConfigSchema($strategy, $key, $scope['configSchema'] ?? []),
            ];
        }

        if ($items === []) {
            throw new RuntimeException(sprintf('Data policy strategy [%s] must define scopes.', $strategy));
        }

        $seen = [];
        foreach ($items as $item) {
            if (isset($seen[$item['key']])) {
                throw new RuntimeException(sprintf('Duplicate data policy scope [%s.%s].', $strategy, $item['key']));
            }
            $seen[$item['key']] = true;
        }

        usort($items, static fn (array $a, array $b): int => [$a['sort'] ?? 0, $a['key']] <=> [$b['sort'] ?? 0, $b['key']]);

        return $items;
    }

    /**
     * @param mixed $schema
     * @return list<array<string, mixed>>
     */
    protected function normalizeConfigSchema(string $strategy, string $scope, mixed $schema): array
    {
        $items = [];
        foreach ((array) $schema as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = trim((string) ($field['key'] ?? ''));
            $type = trim((string) ($field['type'] ?? ''));
            if ($key === '' || $type === '') {
                throw new RuntimeException(sprintf('Data policy config schema [%s.%s] must define key and type.', $strategy, $scope));
            }
            $items[] = [
                'key' => $key,
                'type' => $type,
                'label' => (string) ($field['label'] ?? $key),
                'i18n' => (string) ($field['i18n'] ?? ''),
                'sort' => (int) ($field['sort'] ?? count($items)),
            ];
        }

        $seen = [];
        foreach ($items as $item) {
            if (isset($seen[$item['key']])) {
                throw new RuntimeException(sprintf('Duplicate data policy config field [%s.%s.%s].', $strategy, $scope, $item['key']));
            }
            $seen[$item['key']] = true;
        }

        usort($items, static fn (array $a, array $b): int => [$a['sort'] ?? 0, $a['key']] <=> [$b['sort'] ?? 0, $b['key']]);

        return $items;
    }

    protected function validationError(string $reason): never
    {
        throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, [
            'field' => 'dataPolicies',
            'reason' => $reason,
        ]);
    }
}
