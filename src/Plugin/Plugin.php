<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Plugin;

class Plugin
{
    /**
     * @param array<string, mixed> $configDefaults
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $version,
        public readonly array $configDefaults,
        public readonly bool $enabled,
    ) {
    }

    public function id(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function sourcePaths(): array
    {
        return $this->existingDirectories(array_map(
            fn (string $directory): string => $this->path . '/' . $directory,
            ['Http', 'Service', 'Repository', 'Model', 'Request', 'Vo', 'Event', 'Listener', 'Library'],
        ));
    }

    public function languagePath(): ?string
    {
        $languagePath = $this->path . '/resources/lang';

        return is_dir($languagePath) ? $languagePath : null;
    }

    public function menuResourceFile(): ?string
    {
        $file = $this->path . '/resources/menus.php';

        return is_file($file) ? $file : null;
    }

    public function permissionResourceFile(): ?string
    {
        $file = $this->path . '/resources/permissions.php';

        return is_file($file) ? $file : null;
    }

    public function dataPolicyResourceFile(): ?string
    {
        $file = $this->path . '/resources/data_policies.php';

        return is_file($file) ? $file : null;
    }

    /**
     * @return list<string>
     */
    public function migrationPaths(): array
    {
        return $this->existingDirectories([
            $this->path . '/Database/Migrations',
        ]);
    }

    /**
     * @return list<string>
     */
    public function seederPaths(): array
    {
        return $this->existingDirectories([
            $this->path . '/Database/Seeders',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return $this->configDefaults;
    }

    /**
     * @param list<string|null> $paths
     * @return list<string>
     */
    protected function existingDirectories(array $paths): array
    {
        return array_values(array_filter($paths, static fn (?string $path): bool => $path !== null && is_dir($path)));
    }
}
