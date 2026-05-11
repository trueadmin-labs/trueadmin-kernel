<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

final readonly class DataPolicyTarget
{
    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(public array $fields = [])
    {
    }

    /**
     * @param array<string, mixed>|self $target
     */
    public static function make(array|self $target = []): self
    {
        return $target instanceof self ? $target : new self($target);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->fields[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function array(string $key): array
    {
        $value = $this->get($key, []);

        return is_array($value) ? $value : [];
    }
}
