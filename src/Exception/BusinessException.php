<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Exception;

use RuntimeException;
use Throwable;
use TrueAdmin\Kernel\Constant\ErrorCodeInterface;

class BusinessException extends RuntimeException
{
    public function __construct(
        ErrorCodeInterface $errorCode,
        private readonly int $httpStatus = 400,
        private readonly array $params = [],
        ?Throwable $previous = null
    ) {
        $this->businessCode = $errorCode->code();

        parent::__construct($errorCode->message($params), 0, $previous);
    }

    private readonly string $businessCode;

    public function businessCode(): string
    {
        return $this->businessCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function params(): array
    {
        return $this->params;
    }
}
