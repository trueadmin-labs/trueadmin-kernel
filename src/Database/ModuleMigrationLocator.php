<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database;

use TrueAdmin\Kernel\Plugin\PluginRepository;

class ModuleMigrationLocator
{
    public function __construct(private readonly ?PluginRepository $plugins = null)
    {
    }

    /**
     * @return list<string>
     */
    public function migrationPaths(): array
    {
        return $this->existingDirectories([
            ...glob(BASE_PATH . '/app/Module/*/Database/Migrations') ?: [],
            ...($this->plugins?->migrationPaths() ?? []),
        ]);
    }

    /**
     * @return list<string>
     */
    public function seederPaths(): array
    {
        return $this->existingDirectories([
            ...glob(BASE_PATH . '/app/Module/*/Database/Seeders') ?: [],
            ...($this->plugins?->seederPaths() ?? []),
        ]);
    }

    /**
     * @param array<int, string|false> $paths
     * @return list<string>
     */
    protected function existingDirectories(array $paths): array
    {
        $directories = array_values(array_filter(
            array_map(static fn ($path): string => is_string($path) ? $path : '', $paths),
            static fn (string $path): bool => $path !== '' && is_dir($path)
        ));

        sort($directories);

        return array_values(array_unique($directories));
    }
}
