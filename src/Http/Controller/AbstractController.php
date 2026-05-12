<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TrueAdmin\Kernel\Http\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use TrueAdmin\Kernel\Stream\SseStreamResponder;

abstract class AbstractController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    /**
     * @template TReturn
     * @param callable(): TReturn $handler
     */
    protected function stream(callable $handler, string $completedMessage = '处理完成'): mixed
    {
        return $this->container->get(SseStreamResponder::class)->run($handler, $completedMessage);
    }
}
