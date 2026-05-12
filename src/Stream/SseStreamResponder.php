<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Stream;

use Hyperf\Context\ResponseContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Swoole\Http\Response as SwooleResponse;
use Throwable;

class SseStreamResponder
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly StdoutLoggerInterface $logger,
    ) {
    }

    /**
     * @template TReturn
     * @param callable(): TReturn $handler
     * @return TReturn|null
     */
    public function run(callable $handler, string $completedMessage = '处理完成'): mixed
    {
        $swooleResponse = $this->swooleResponse();
        $this->prepareHeaders($swooleResponse);
        $writer = new SseStreamWriter($swooleResponse);
        $result = null;

        try {
            $result = StreamProgressContext::runWithListener(
                static fn (StreamProgressEvent $event) => $writer->progress($event),
                static function () use ($handler, $writer, $completedMessage): mixed {
                    $result = $handler();

                    $writer->result($result);
                    $writer->completed($completedMessage);

                    return $result;
                },
            );
        } catch (Throwable $throwable) {
            $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
            $this->logger->error($throwable->getTraceAsString());

            if ($writer->isWritable()) {
                $writer->error($throwable);
            }
        } finally {
            if ($writer->isWritable()) {
                $writer->done();
                $writer->end();
            }
        }

        return $result;
    }

    protected function swooleResponse(): SwooleResponse
    {
        $connection = ResponseContext::get()->getConnection();
        $socket = $connection?->getSocket();

        if (! $socket instanceof SwooleResponse) {
            throw new \RuntimeException('SSE stream requires a Swoole HTTP response connection.');
        }

        return $socket;
    }

    protected function prepareHeaders(SwooleResponse $response): void
    {
        $response->header('Content-Type', 'text/event-stream; charset=utf-8');
        $response->header('Cache-Control', 'no-cache, no-transform');
        $response->header('Connection', 'keep-alive');
        $response->header('X-Accel-Buffering', 'no');
        $response->header('Access-Control-Allow-Credentials', 'true');

        $origin = $this->request->getHeaderLine('Origin');
        if ($origin !== '') {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Vary', 'Origin');
        }
    }
}
