<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Stream;

use RuntimeException;
use Swoole\Http\Response as SwooleResponse;
use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Exception\BusinessException;
use TrueAdmin\Kernel\Http\ApiResponse;
use Throwable;

class SseStreamWriter
{
    private bool $finished = false;

    public function __construct(private readonly SwooleResponse $response)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function event(array $data): void
    {
        $this->write('data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n\n");
    }

    public function progress(StreamProgressEvent $event): void
    {
        $this->event($event->toArray());
    }

    public function result(mixed $response): void
    {
        $this->event([
            'type' => 'result',
            'message' => 'success',
            'response' => $response,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function completed(string $message = '处理完成', array $payload = []): void
    {
        $data = [
            'type' => 'completed',
            'message' => $message,
        ];

        if ($payload !== []) {
            $data['payload'] = $payload;
        }

        $this->event($data);
    }

    public function error(Throwable $throwable): void
    {
        if ($throwable instanceof BusinessException) {
            $response = ApiResponse::fail(
                $throwable->businessCode(),
                $throwable->getMessage(),
                $throwable->params() ?: null,
            );

            $this->event([
                'type' => 'error',
                'code' => $throwable->businessCode(),
                'message' => $throwable->getMessage(),
                'response' => $response,
            ]);

            return;
        }

        $response = ApiResponse::fail(ErrorCode::SERVER_ERROR->code(), ErrorCode::SERVER_ERROR->message());

        $this->event([
            'type' => 'error',
            'code' => ErrorCode::SERVER_ERROR->code(),
            'message' => ErrorCode::SERVER_ERROR->message(),
            'response' => $response,
        ]);
    }

    public function done(): void
    {
        if ($this->finished) {
            return;
        }

        $this->write("data: [DONE]\n\n");
        $this->finished = true;
    }

    public function end(): void
    {
        $this->response->end();
    }

    public function isWritable(): bool
    {
        return $this->response->isWritable();
    }

    protected function write(string $chunk): void
    {
        if (! $this->response->isWritable()) {
            throw new RuntimeException('SSE client connection is not writable.');
        }

        if (! $this->response->write($chunk)) {
            throw new RuntimeException('SSE client connection was interrupted.');
        }
    }
}
