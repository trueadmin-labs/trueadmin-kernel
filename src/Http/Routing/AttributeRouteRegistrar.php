<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Routing;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Router\Router;
use TrueAdmin\Kernel\Http\Attribute\AdminController;
use TrueAdmin\Kernel\Http\Attribute\AdminDelete;
use TrueAdmin\Kernel\Http\Attribute\AdminGet;
use TrueAdmin\Kernel\Http\Attribute\AdminPost;
use TrueAdmin\Kernel\Http\Attribute\AdminPut;
use TrueAdmin\Kernel\Http\Attribute\ClientController;
use TrueAdmin\Kernel\Http\Attribute\ClientDelete;
use TrueAdmin\Kernel\Http\Attribute\ClientGet;
use TrueAdmin\Kernel\Http\Attribute\ClientPost;
use TrueAdmin\Kernel\Http\Attribute\ClientPut;
use TrueAdmin\Kernel\Http\Attribute\OpenController;
use TrueAdmin\Kernel\Http\Attribute\OpenDelete;
use TrueAdmin\Kernel\Http\Attribute\OpenGet;
use TrueAdmin\Kernel\Http\Attribute\OpenPost;
use TrueAdmin\Kernel\Http\Attribute\OpenPut;
use TrueAdmin\Kernel\Http\Attribute\RouteMapping;

class AttributeRouteRegistrar
{
    /**
     * @return list<array{methods: list<string>, path: string, action: string, middleware: list<class-string>, name: string}>
     */
    public function routes(): array
    {
        $routes = [];

        foreach ($this->methodMappings() as $item) {
            $controller = $this->controller($item['class']);
            if ($controller === null) {
                continue;
            }

            $mapping = $item['annotation'];
            $routes[] = [
                'methods' => $mapping->methods,
                'path' => $this->routePath($controller['path'], $mapping->path),
                'action' => $item['class'] . '@' . $item['method'],
                'middleware' => array_values(array_unique([...$controller['middleware'], ...$mapping->middleware])),
                'name' => $mapping->name,
            ];
        }

        usort($routes, static fn (array $a, array $b): int => [$a['path'], $a['action']] <=> [$b['path'], $b['action']]);

        return $routes;
    }

    public function register(): void
    {
        foreach ($this->routes() as $route) {
            Router::addRoute($route['methods'], $route['path'], $route['action'], [
                'middleware' => $route['middleware'],
            ]);
        }
    }

    /**
     * @return array{path: string, middleware: list<class-string>}|null
     */
    protected function controller(string $class): ?array
    {
        foreach ([AdminController::class, ClientController::class, OpenController::class] as $annotationClass) {
            $annotation = AnnotationCollector::getClassAnnotation($class, $annotationClass);
            if ($annotation === null) {
                continue;
            }

            return [
                'path' => $annotation->path,
                'middleware' => $annotation->middleware,
            ];
        }

        return null;
    }

    /**
     * @return list<array{class: class-string, method: string, annotation: RouteMapping}>
     */
    protected function methodMappings(): array
    {
        $items = [];

        foreach ($this->mappingAnnotations() as $annotationClass) {
            foreach (AnnotationCollector::getMethodsByAnnotation($annotationClass) as $item) {
                if ($item['annotation'] instanceof RouteMapping) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @return list<class-string<RouteMapping>>
     */
    protected function mappingAnnotations(): array
    {
        return [
            AdminGet::class,
            AdminPost::class,
            AdminPut::class,
            AdminDelete::class,
            ClientGet::class,
            ClientPost::class,
            ClientPut::class,
            ClientDelete::class,
            OpenGet::class,
            OpenPost::class,
            OpenPut::class,
            OpenDelete::class,
        ];
    }

    protected function routePath(string $controllerPath, string $methodPath): string
    {
        if (str_starts_with($methodPath, '/')) {
            return $this->join($methodPath);
        }

        return $this->join($controllerPath, $methodPath);
    }

    protected function join(string ...$segments): string
    {
        $path = implode('/', array_map(static fn (string $segment): string => trim($segment, '/'), $segments));
        $path = '/' . trim(preg_replace('#/+#', '/', $path) ?? '', '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
