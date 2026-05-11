<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

use Hyperf\Context\Context as HyperfContext;

final class Context
{
    private const KEY = 'trueadmin.data_scope';

    public static function set(array $payload): void
    {
        HyperfContext::set(self::KEY, $payload);
    }

    public static function get(): ?array
    {
        return HyperfContext::get(self::KEY);
    }

    public static function clear(): void
    {
        HyperfContext::destroy(self::KEY);
    }

    public static function runWith(array $payload, callable $callback): mixed
    {
        $previous = self::get();

        self::set($payload);

        try {
            return $callback();
        } finally {
            self::restore($previous);
        }
    }

    private static function restore(?array $payload): void
    {
        self::clear();

        if ($payload !== null) {
            self::set($payload);
        }
    }
}
