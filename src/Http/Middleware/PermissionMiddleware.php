<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Middleware;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Context\Actor;
use TrueAdmin\Kernel\Context\ActorContext;
use TrueAdmin\Kernel\Exception\BusinessException;
use TrueAdmin\Kernel\Http\Attribute\Permission;
use TrueAdmin\Kernel\Http\PermissionProviderInterface;

class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly PermissionProviderInterface $permissions)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permission = $this->permissionFromRoute($request);
        if ($permission === null) {
            return $handler->handle($request);
        }

        $actor = ActorContext::principal();
        if ($actor === null || ! $this->allows($actor, $permission)) {
            throw new BusinessException(ErrorCode::FORBIDDEN, 403, [
                'permission' => implode(',', $permission->codes()),
                'mode' => $permission->mode(),
            ]);
        }

        return $handler->handle($request);
    }

    protected function allows(Actor $actor, Permission $permission): bool
    {
        return match ($permission->mode()) {
            'anyOf' => $this->allowsAny($actor, $permission->codes()),
            'allOf' => $this->allowsAll($actor, $permission->codes()),
            default => $this->permissions->can($actor, $permission->code),
        };
    }

    /**
     * @param list<string> $permissions
     */
    protected function allowsAny(Actor $actor, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->permissions->can($actor, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $permissions
     */
    protected function allowsAll(Actor $actor, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->permissions->can($actor, $permission)) {
                return false;
            }
        }

        return true;
    }

    protected function permissionFromRoute(ServerRequestInterface $request): ?Permission
    {
        $dispatched = $request->getAttribute(Dispatched::class);
        $callback = $dispatched instanceof Dispatched ? $dispatched->handler?->callback : null;

        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback, 2);
        } elseif (is_array($callback) && isset($callback[0], $callback[1]) && is_string($callback[0]) && is_string($callback[1])) {
            [$class, $method] = $callback;
        } else {
            return null;
        }

        $methodAnnotation = AnnotationCollector::getClassMethodAnnotation($class, $method)[Permission::class] ?? null;
        if ($methodAnnotation instanceof Permission) {
            return $methodAnnotation;
        }

        $classAnnotation = AnnotationCollector::getClassAnnotation($class, Permission::class);

        return $classAnnotation instanceof Permission ? $classAnnotation : null;
    }
}
