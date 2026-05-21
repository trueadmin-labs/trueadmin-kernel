<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $meta
     */
    public static function success(mixed $data = null, array $meta = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => self::meta($meta),
        ];
    }

    /**
     * @param array<string, mixed> $details
     * @param array<string, mixed> $meta
     */
    public static function fail(string $code, string $message, array $details = [], array $meta = []): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => self::meta($meta),
        ];
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private static function meta(array $meta): array
    {
        return [
            'requestId' => $meta['requestId'] ?? null,
            'timestamp' => $meta['timestamp'] ?? date(DATE_ATOM),
            ...$meta,
        ];
    }
}
