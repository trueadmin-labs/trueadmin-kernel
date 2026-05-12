<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Support;

final class Password
{
    public static function make(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
