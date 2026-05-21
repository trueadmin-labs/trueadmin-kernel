<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Exception;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Http\ApiResponse;

class TrueAdminExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof BusinessException) {
            $this->stopPropagation();

            return $this->businessExceptionResponse($throwable, $response);
        }

        if ($throwable instanceof ValidationException) {
            $this->stopPropagation();

            return $this->validationExceptionResponse($throwable, $response);
        }

        $this->logUnexpected($throwable);
        $this->stopPropagation();

        return $this->serverErrorResponse($throwable, $response);
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    protected function businessExceptionResponse(BusinessException $throwable, ResponseInterface $response): ResponseInterface
    {
        return $this->json(
            $response,
            $throwable->httpStatus(),
            ApiResponse::fail($throwable->businessCode(), $throwable->getMessage(), $throwable->params()),
        );
    }

    protected function validationExceptionResponse(ValidationException $throwable, ResponseInterface $response): ResponseInterface
    {
        return $this->json(
            $response,
            422,
            ApiResponse::fail(ErrorCode::VALIDATION_FAILED->code(), ErrorCode::VALIDATION_FAILED->message(), [
                'fields' => $throwable->errors(),
            ]),
        );
    }

    protected function serverErrorResponse(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        return $this->json(
            $response,
            500,
            ApiResponse::fail(ErrorCode::SERVER_ERROR->code(), ErrorCode::SERVER_ERROR->message()),
        );
    }

    protected function logUnexpected(Throwable $throwable): void
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function json(ResponseInterface $response, int $status, array $payload): ResponseInterface
    {
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status)
            ->withBody(new SwooleStream(json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            )));
    }
}
