<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http;

use TrueAdmin\Kernel\Constant\ErrorCode;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'success'): array
    {
        return [
            'code' => ErrorCode::SUCCESS->code(),
            'message' => $message,
            'data' => $data,
        ];
    }

    public static function fail(string $code, string $message, mixed $data = null): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }
}
