<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Stream;

use Hyperf\Context\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class StreamProgress
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function emit(
        string $type,
        string $message,
        ?string $module = null,
        ?string $stage = null,
        ?int $current = null,
        ?int $total = null,
        ?int $percent = null,
        array $payload = [],
    ): void {
        $event = new StreamProgressEvent(
            type: $type,
            message: $message,
            module: $module,
            stage: $stage,
            current: $current,
            total: $total,
            percent: $percent,
            payload: $payload,
        );

        StreamProgressContext::emit($event);
        self::dispatch($event);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function progress(
        string $message,
        ?string $module = null,
        ?string $stage = null,
        ?int $current = null,
        ?int $total = null,
        ?int $percent = null,
        array $payload = [],
    ): void {
        self::emit('progress', $message, $module, $stage, $current, $total, $percent, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function debug(string $message, array $payload = [], ?string $module = null): void
    {
        self::emit('debug', $message, $module, payload: $payload);
    }

    public static function isListening(): bool
    {
        return StreamProgressContext::isActive();
    }

    private static function dispatch(StreamProgressEvent $event): void
    {
        if (! ApplicationContext::hasContainer()) {
            return;
        }

        try {
            ApplicationContext::getContainer()->get(EventDispatcherInterface::class)->dispatch($event);
        } catch (Throwable) {
            // Progress observers must not change the result of the business method.
        }
    }
}
