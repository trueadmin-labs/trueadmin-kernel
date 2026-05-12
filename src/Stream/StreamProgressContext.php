<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Stream;

use Hyperf\Context\Context;

final class StreamProgressContext
{
    private const LISTENERS_KEY = 'trueadmin.stream_progress.listeners';

    /**
     * @param callable(StreamProgressEvent): void $listener
     */
    public static function pushListener(callable $listener): void
    {
        $listeners = self::listeners();
        $listeners[] = $listener;

        Context::set(self::LISTENERS_KEY, $listeners);
    }

    public static function emit(StreamProgressEvent $event): void
    {
        foreach (self::listeners() as $listener) {
            $listener($event);
        }
    }

    public static function isActive(): bool
    {
        return self::listeners() !== [];
    }

    /**
     * @template TReturn
     * @param callable(StreamProgressEvent): void $listener
     * @param callable(): TReturn $callback
     * @return TReturn
     */
    public static function runWithListener(callable $listener, callable $callback): mixed
    {
        $previous = self::listeners();
        Context::set(self::LISTENERS_KEY, [...$previous, $listener]);

        try {
            return $callback();
        } finally {
            if ($previous === []) {
                Context::destroy(self::LISTENERS_KEY);
            } else {
                Context::set(self::LISTENERS_KEY, $previous);
            }
        }
    }

    /**
     * @return array<int, callable(StreamProgressEvent): void>
     */
    private static function listeners(): array
    {
        $listeners = Context::get(self::LISTENERS_KEY, []);

        return is_array($listeners) ? $listeners : [];
    }
}
