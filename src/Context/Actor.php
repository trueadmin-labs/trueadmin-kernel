<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Context;

final readonly class Actor
{
    public function __construct(
        public string $type,
        public int|string $id,
        public string $name,
        public string $source = 'http',
        public ?string $reason = null,
        public array $claims = [],
    ) {
    }

    public static function system(?string $reason = null): self
    {
        return new self('system', 'system', 'System', 'system', $reason);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
            'source' => $this->source,
            'reason' => $this->reason,
            'claims' => $this->claims,
        ];
    }
}
