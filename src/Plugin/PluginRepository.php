<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Plugin;

use Hyperf\Contract\ConfigInterface;

class PluginRepository
{
    public function __construct(private readonly ConfigInterface $config)
    {
    }

    /**
     * @return list<Plugin>
     */
    public function all(): array
    {
        $plugins = [];
        $disabled = $this->stringList('plugins.disabled');

        foreach ($this->installedPlugins() as $name => $definition) {
            $path = $definition['path'] ?? null;
            if (! is_string($name) || $name === '' || ! is_string($path) || $path === '') {
                continue;
            }

            $isEnabled = (bool) ($definition['enabled'] ?? true);
            if (in_array($name, $disabled, true)) {
                $isEnabled = false;
            }

            $version = $definition['version'] ?? 'unknown';
            $defaults = $definition['defaults'] ?? [];

            $plugins[] = new Plugin(
                name: $name,
                path: rtrim($path, '/'),
                version: is_string($version) && $version !== '' ? $version : 'unknown',
                configDefaults: is_array($defaults) ? $defaults : [],
                enabled: $isEnabled,
            );
        }

        usort($plugins, static fn (Plugin $a, Plugin $b): int => $a->name <=> $b->name);

        return $plugins;
    }

    /**
     * @return list<Plugin>
     */
    public function enabled(): array
    {
        return array_values(array_filter($this->all(), static fn (Plugin $plugin): bool => $plugin->enabled));
    }

    /**
     * @return list<string>
     */
    public function sourcePaths(): array
    {
        $paths = [];

        foreach ($this->enabled() as $plugin) {
            $paths = [...$paths, ...$plugin->sourcePaths()];
        }

        sort($paths);

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    public function migrationPaths(): array
    {
        $paths = [];

        foreach ($this->enabled() as $plugin) {
            $paths = [...$paths, ...$plugin->migrationPaths()];
        }

        sort($paths);

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    public function seederPaths(): array
    {
        $paths = [];

        foreach ($this->enabled() as $plugin) {
            $paths = [...$paths, ...$plugin->seederPaths()];
        }

        sort($paths);

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    public function menuResourceFiles(): array
    {
        $files = [];

        foreach ($this->enabled() as $plugin) {
            $file = $plugin->menuResourceFile();
            if ($file !== null) {
                $files[] = $file;
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    public function dataPolicyResourceFiles(): array
    {
        $files = [];

        foreach ($this->enabled() as $plugin) {
            $file = $plugin->dataPolicyResourceFile();
            if ($file !== null) {
                $files[] = $file;
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function installedPlugins(): array
    {
        $installed = $this->config->get('plugins.installed', []);
        if (! is_array($installed)) {
            return [];
        }

        return array_filter($installed, static fn ($definition): bool => is_array($definition));
    }

    /**
     * @return list<string>
     */
    protected function stringList(string $key): array
    {
        $value = $this->config->get($key, []);
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn ($item): bool => is_string($item) && $item !== ''));
    }
}
