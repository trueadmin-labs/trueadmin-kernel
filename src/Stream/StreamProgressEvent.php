<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Stream;

final class StreamProgressEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly string $message,
        public readonly ?string $module = null,
        public readonly ?string $stage = null,
        public readonly ?int $current = null,
        public readonly ?int $total = null,
        public readonly ?int $percent = null,
        public readonly array $payload = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'message' => $this->message,
            'module' => $this->module,
            'stage' => $this->stage,
            'current' => $this->current,
            'total' => $this->total,
            'percent' => $this->percent,
            'payload' => $this->payload === [] ? null : $this->payload,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
