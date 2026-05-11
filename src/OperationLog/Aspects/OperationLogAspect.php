<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\OperationLog\Aspects;

use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Psr\EventDispatcher\EventDispatcherInterface;
use TrueAdmin\Kernel\OperationLog\Attribute\OperationLog;
use TrueAdmin\Kernel\OperationLog\Event\OperationLogged;

#[Aspect]
final class OperationLogAspect extends AbstractAspect
{
    public array $annotations = [
        OperationLog::class,
    ];

    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $attribute = Arr::get($proceedingJoinPoint->getAnnotationMetadata()->method, OperationLog::class)
            ?? Arr::get($proceedingJoinPoint->getAnnotationMetadata()->class, OperationLog::class);

        if (! $attribute instanceof OperationLog) {
            return $proceedingJoinPoint->process();
        }

        $result = $proceedingJoinPoint->process();

        $this->dispatcher->dispatch(new OperationLogged(
            module: $attribute->module,
            action: $attribute->action,
            remark: $attribute->remark,
            context: [
                'class' => $proceedingJoinPoint->className,
                'method' => $proceedingJoinPoint->methodName,
            ],
        ));

        return $result;
    }
}
