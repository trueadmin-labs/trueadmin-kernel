<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Metadata;

class OpenApiDocumentBuilder
{
    public function __construct(private readonly InterfaceMetadataScanner $scanner)
    {
    }

    public function build(): array
    {
        $metadata = $this->scanner->scan();
        $permissionRules = $this->indexByAction($metadata['permissionRules'] ?? []);
        $openapi = $this->indexByAction($metadata['openapi'] ?? []);
        $paths = [];

        foreach ($metadata['routes'] ?? [] as $route) {
            $action = (string) $route['action'];
            $permissionRule = $permissionRules[$action] ?? null;
            $spec = $openapi[$action] ?? null;
            $path = $this->normalizePath((string) $route['path']);

            foreach ($route['methods'] as $method) {
                $verb = strtolower((string) $method);
                if ($verb === 'head') {
                    continue;
                }

                $paths[$path][$verb] = $this->operation($route, $permissionRule, $spec);
            }
        }

        ksort($paths);

        return [
            'openapi' => '3.1.0',
            'info' => $this->info(),
            'servers' => $this->servers(),
            'paths' => $paths,
            'components' => $this->components(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function info(): array
    {
        return [
            'title' => 'TrueAdmin API',
            'version' => '0.1.0',
            'description' => 'Generated from TrueAdmin controller attributes. Code annotations provide default interface metadata.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function servers(): array
    {
        return [
            ['url' => '/'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function components(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
        ];
    }

    protected function operation(array $route, ?array $permissionRule, ?array $spec): array
    {
        $summary = (string) ($spec['summary'] ?? '');
        if ($summary === '') {
            $summary = (string) ($permissionRule['title'] ?? $route['name'] ?? $route['action']);
        }
        if ($summary === '') {
            $summary = (string) $route['action'];
        }

        $tags = $spec['tags'] ?? [];
        if ($tags === [] && isset($permissionRule['group']) && $permissionRule['group'] !== '') {
            $tags = [(string) $permissionRule['group']];
        }

        $operation = [
            'operationId' => $this->operationId((string) $route['action'], (string) ($route['name'] ?? '')),
            'summary' => $summary,
            'description' => (string) ($spec['description'] ?? ''),
            'tags' => $tags,
            'responses' => [
                '200' => [
                    'description' => 'OK',
                ],
            ],
            'x-trueadmin' => [
                'action' => $route['action'],
                'permission' => $permissionRule['code'] ?? null,
                'permissionMode' => $permissionRule['mode'] ?? null,
                'permissions' => $permissionRule['codes'] ?? [],
                'authenticated' => $this->routeRequiresAuth($route),
            ],
        ];

        if ($this->routeRequiresAuth($route)) {
            $operation['security'] = $spec['security'] ?? [['bearerAuth' => []]];
        }

        if (($spec['deprecated'] ?? false) === true) {
            $operation['deprecated'] = true;
        }

        if (($spec['errorCodes'] ?? []) !== []) {
            $operation['x-error-codes'] = $spec['errorCodes'];
        }

        return $operation;
    }

    protected function normalizePath(string $path): string
    {
        return preg_replace('#\{([^}/]+)\}#', '{$1}', $path) ?? $path;
    }

    protected function routeRequiresAuth(array $route): bool
    {
        foreach ($route['middleware'] ?? [] as $middleware) {
            if (is_string($middleware) && str_ends_with($middleware, 'AuthMiddleware')) {
                return true;
            }
        }

        return false;
    }

    protected function operationId(string $action, string $name): string
    {
        $source = $name !== '' ? $name : str_replace('\\', '_', str_replace('@', '_', $action));
        $id = preg_replace('/[^A-Za-z0-9_]+/', '_', $source) ?? $source;

        return trim($id, '_');
    }

    protected function indexByAction(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $action = (string) ($item['action'] ?? '');
            if ($action !== '') {
                $indexed[$action] = $item;
            }
        }

        return $indexed;
    }
}
