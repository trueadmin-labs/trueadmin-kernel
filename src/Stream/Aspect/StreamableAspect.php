<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Stream\Aspect;

use TrueAdmin\Kernel\Stream\SseStreamResponder;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Contract\RequestInterface;
use TrueAdmin\Kernel\Http\Attribute\Streamable;

#[Aspect]
class StreamableAspect extends AbstractAspect
{
    public array $annotations = [
        Streamable::class,
    ];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly SseStreamResponder $responder,
    ) {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $attribute = Arr::get($proceedingJoinPoint->getAnnotationMetadata()->method, Streamable::class)
            ?? Arr::get($proceedingJoinPoint->getAnnotationMetadata()->class, Streamable::class);

        if (! $attribute instanceof Streamable || ! $attribute->enabled || ! $this->expectsStream($attribute)) {
            return $proceedingJoinPoint->process();
        }

        return $this->responder->run(
            static fn () => $proceedingJoinPoint->process(),
            $attribute->completedMessage,
        );
    }

    protected function expectsStream(Streamable $attribute): bool
    {
        if ($this->request->header('X-Stream-Response', '') === '1') {
            return true;
        }

        if ((string) $this->request->input('_stream', '') === '1') {
            return true;
        }

        $accept = strtolower($this->request->getHeaderLine('Accept'));
        foreach ($attribute->requestHeaders as $header) {
            if ($header !== '' && str_contains($accept, strtolower($header))) {
                return true;
            }
        }

        return false;
    }
}
