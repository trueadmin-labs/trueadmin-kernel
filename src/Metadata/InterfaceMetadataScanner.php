<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Metadata;

use TrueAdmin\Kernel\Http\Routing\AttributeRouteRegistrar;
use TrueAdmin\Kernel\Plugin\PluginRepository;
use Hyperf\Di\Annotation\AnnotationCollector;
use TrueAdmin\Kernel\Http\Attribute\OpenApi;
use TrueAdmin\Kernel\Http\Attribute\Permission;

class InterfaceMetadataScanner
{
    protected const MENU_TYPES = ['directory', 'menu', 'button', 'link'];

    protected const MENU_STATUSES = ['enabled', 'disabled'];

    protected const LINK_OPEN_MODES = ['blank', 'self', 'iframe'];

    /**
     * @var null|list<array<string, mixed>>
     */
    private ?array $resourceMenus = null;

    public function __construct(
        private readonly AttributeRouteRegistrar $routes,
        private readonly PluginRepository $plugins,
    ) {
    }

    public function scan(): array
    {
        return [
            'routes' => $this->routes->routes(),
            'menus' => $this->menus(),
            'permissions' => $this->permissions(),
            'permissionRules' => $this->permissionRules(),
            'openapi' => $this->openapi(),
        ];
    }

    protected function menus(): array
    {
        $menus = [];
        foreach ($this->resourceMenus() as $menu) {
            $this->appendMenu($menus, $menu);
        }

        uasort($menus, static fn (array $a, array $b): int => [$a['sort'], $a['code']] <=> [$b['sort'], $b['code']]);

        return array_values($menus);
    }

    /**
     * @param array<string, array<string, mixed>> $menus
     * @param array<string, mixed> $menu
     */
    protected function appendMenu(array &$menus, array $menu): void
    {
        $normalized = $this->normalizeMenu($menu);
        $code = $normalized['code'];
        if (isset($menus[$code])) {
            throw new \RuntimeException(sprintf('Duplicate menu code [%s] found while scanning interface metadata.', $code));
        }

        $menus[$code] = $normalized;
    }

    /**
     * @param array<string, mixed> $menu
     * @return array<string, mixed>
     */
    protected function normalizeMenu(array $menu): array
    {
        $code = isset($menu['code']) ? trim((string) $menu['code']) : '';
        if ($code === '') {
            throw new \RuntimeException('Menu code is required while scanning interface metadata.');
        }

        if (preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $code) !== 1) {
            throw new \RuntimeException(sprintf('Menu [%s] has an invalid code.', $code));
        }

        $type = isset($menu['type']) && (string) $menu['type'] !== '' ? (string) $menu['type'] : 'menu';
        if (! in_array($type, static::MENU_TYPES, true)) {
            throw new \RuntimeException(sprintf('Menu [%s] has an unsupported type [%s].', $code, $type));
        }

        $status = isset($menu['status']) && (string) $menu['status'] !== '' ? (string) $menu['status'] : 'enabled';
        if (! in_array($status, static::MENU_STATUSES, true)) {
            throw new \RuntimeException(sprintf('Menu [%s] has an unsupported status [%s].', $code, $status));
        }

        $path = isset($menu['path']) ? trim((string) $menu['path']) : '';
        $url = isset($menu['url']) ? trim((string) $menu['url']) : '';
        $openMode = (string) ($menu['openMode'] ?? '');
        $showLinkHeader = $this->normalizeBoolean($menu['showLinkHeader'] ?? false);

        if ($type === 'menu' && $path === '') {
            throw new \RuntimeException(sprintf('Menu [%s] with type [menu] must define a path.', $code));
        }

        if ($type === 'link') {
            if ($url === '') {
                throw new \RuntimeException(sprintf('Menu [%s] with type [link] must define a url.', $code));
            }
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (! in_array($scheme, ['http', 'https'], true) || filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new \RuntimeException(sprintf('Menu [%s] has an invalid link url.', $code));
            }
            $openMode = $openMode !== '' ? $openMode : 'blank';
            if (! in_array($openMode, static::LINK_OPEN_MODES, true)) {
                throw new \RuntimeException(sprintf('Menu [%s] has an unsupported link open mode [%s].', $code, $openMode));
            }
        } else {
            $url = '';
            $openMode = '';
            $showLinkHeader = false;
        }

        return [
            'class' => isset($menu['class']) ? (string) $menu['class'] : '',
            'code' => $code,
            'title' => isset($menu['title']) && (string) $menu['title'] !== '' ? (string) $menu['title'] : $code,
            'path' => $path,
            'parent' => isset($menu['parent']) ? trim((string) $menu['parent']) : '',
            'permission' => isset($menu['permission']) ? (string) $menu['permission'] : '',
            'icon' => isset($menu['icon']) ? (string) $menu['icon'] : '',
            'sort' => isset($menu['sort']) ? (int) $menu['sort'] : 0,
            'type' => $type,
            'status' => $status,
            'url' => $url,
            'openMode' => $openMode,
            'showLinkHeader' => $showLinkHeader,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function resourceMenus(): array
    {
        if ($this->resourceMenus !== null) {
            return $this->resourceMenus;
        }

        $menus = [];
        foreach ($this->menuResourceFiles() as $file) {
            $items = require $file;
            if (! is_array($items)) {
                throw new \RuntimeException(sprintf('Menu resource file [%s] must return an array.', $file));
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    throw new \RuntimeException(sprintf('Menu resource file [%s] contains an invalid menu item.', $file));
                }

                $menus[] = $item;
            }
        }

        return $this->resourceMenus = $menus;
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * @return list<string>
     */
    protected function menuResourceFiles(): array
    {
        $files = [
            ...(glob(BASE_PATH . '/app/Module/*/resources/menus.php') ?: []),
            ...$this->testingMenuResourceFiles(),
            ...$this->plugins->menuResourceFiles(),
        ];

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    protected function testingMenuResourceFiles(): array
    {
        if ((getenv('APP_ENV') ?: '') !== 'testing') {
            return [];
        }

        return glob(BASE_PATH . '/test/Support/Module/*/resources/menus.php') ?: [];
    }

    protected function permissions(): array
    {
        $permissions = [];
        $routes = $this->routeIndex();
        $definedCodes = $this->definedPermissionCodes();

        foreach ($this->permissionItems() as $item) {
            $annotation = $item['annotation'];
            if (! $annotation instanceof Permission) {
                continue;
            }

            foreach ($annotation->codes() as $code) {
                if (! isset($definedCodes[$code])) {
                    throw new \RuntimeException(sprintf('Permission [%s] is referenced by a rule but is not defined as an atomic permission.', $code));
                }
            }

            if ($annotation->mode() !== 'single') {
                continue;
            }

            $class = (string) $item['class'];
            $method = isset($item['method']) ? (string) $item['method'] : null;
            $action = $method === null ? null : $class . '@' . $method;
            $permissions[$annotation->code] ??= [
                'class' => $class,
                'method' => $method,
                'action' => $action,
                'route' => $action === null ? null : ($routes[$action] ?? null),
                'menu' => $this->menuCodeForPermission($annotation->code),
                'code' => $annotation->code,
                'title' => $annotation->title,
                'group' => $annotation->group,
            ];
        }

        return array_values($permissions);
    }

    protected function permissionRules(): array
    {
        $rules = [];
        $routes = $this->routeIndex();

        foreach ($this->permissionItems() as $item) {
            $annotation = $item['annotation'];
            if (! $annotation instanceof Permission) {
                continue;
            }

            $class = (string) $item['class'];
            $method = isset($item['method']) ? (string) $item['method'] : null;
            $action = $method === null ? null : $class . '@' . $method;
            $rules[] = [
                'class' => $class,
                'method' => $method,
                'action' => $action,
                'route' => $action === null ? null : ($routes[$action] ?? null),
                'menu' => $annotation->mode() === 'single' ? $this->menuCodeForPermission($annotation->code) : '',
                'code' => $annotation->mode() === 'single' ? $annotation->code : null,
                'mode' => $annotation->mode(),
                'codes' => $annotation->codes(),
                'title' => $annotation->title,
                'group' => $annotation->group,
            ];
        }

        return $rules;
    }

    protected function definedPermissionCodes(): array
    {
        $codes = [];

        foreach ($this->permissionResourceFiles() as $file) {
            $items = require $file;
            if (! is_array($items)) {
                throw new \RuntimeException(sprintf('Permission resource file [%s] must return an array.', $file));
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    throw new \RuntimeException(sprintf('Permission resource file [%s] contains an invalid permission item.', $file));
                }

                $code = isset($item['code']) ? trim((string) $item['code']) : '';
                if ($code === '') {
                    throw new \RuntimeException(sprintf('Permission resource file [%s] contains a permission without code.', $file));
                }
                if (isset($codes[$code])) {
                    throw new \RuntimeException(sprintf('Duplicate permission code [%s] found while scanning interface metadata.', $code));
                }

                $codes[$code] = true;
            }
        }

        foreach ($this->resourceMenus() as $menu) {
            $permission = isset($menu['permission']) ? (string) $menu['permission'] : '';
            if ($permission !== '' && ! isset($codes[$permission])) {
                $codes[$permission] = true;
            }
        }

        return $codes;
    }

    /**
     * @return list<string>
     */
    protected function permissionResourceFiles(): array
    {
        $files = [
            ...(glob(BASE_PATH . '/app/Module/*/resources/permissions.php') ?: []),
            ...$this->plugins->permissionResourceFiles(),
        ];

        sort($files);

        return array_values(array_unique($files));
    }

    protected function menuCodeForPermission(string $permission): string
    {
        foreach ($this->resourceMenus() as $menu) {
            if ((string) ($menu['permission'] ?? '') === $permission) {
                return (string) ($menu['code'] ?? '');
            }
        }

        return '';
    }

    protected function permissionItems(): array
    {
        return [
            ...array_map(
                static fn (string $class, Permission $annotation): array => ['class' => $class, 'annotation' => $annotation],
                array_keys(AnnotationCollector::getClassesByAnnotation(Permission::class)),
                array_values(AnnotationCollector::getClassesByAnnotation(Permission::class)),
            ),
            ...AnnotationCollector::getMethodsByAnnotation(Permission::class),
        ];
    }

    protected function openapi(): array
    {
        return array_values(array_map(
            static fn (array $item): array => [
                'class' => $item['class'],
                'method' => $item['method'] ?? null,
                'action' => isset($item['method']) ? $item['class'] . '@' . $item['method'] : null,
                'summary' => $item['annotation']->summary,
                'description' => $item['annotation']->description,
                'request' => $item['annotation']->request,
                'response' => $item['annotation']->response,
                'tags' => $item['annotation']->tags,
                'security' => $item['annotation']->security,
                'errorCodes' => $item['annotation']->errorCodes,
                'deprecated' => $item['annotation']->deprecated,
            ],
            [
                ...array_map(
                    static fn (string $class, OpenApi $annotation): array => ['class' => $class, 'annotation' => $annotation],
                    array_keys(AnnotationCollector::getClassesByAnnotation(OpenApi::class)),
                    array_values(AnnotationCollector::getClassesByAnnotation(OpenApi::class)),
                ),
                ...AnnotationCollector::getMethodsByAnnotation(OpenApi::class),
            ],
        ));
    }

    protected function routeIndex(): array
    {
        $routes = [];
        foreach ($this->routes->routes() as $route) {
            $routes[$route['action']] = $route;
        }

        return $routes;
    }
}
