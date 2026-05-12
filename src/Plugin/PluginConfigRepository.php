<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Plugin;

use Hyperf\Contract\ConfigInterface;

class PluginConfigRepository
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly PluginRepository $plugins,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $pluginName): array
    {
        $plugin = $this->find($pluginName);
        if ($plugin === null) {
            return $this->projectConfig($pluginName);
        }

        return $this->mergeRecursive($plugin->defaultConfig(), $this->projectConfig($pluginName));
    }

    public function value(string $pluginName, string $key, mixed $default = null): mixed
    {
        $config = $this->get($pluginName);

        foreach (explode('.', $key) as $segment) {
            if (! is_array($config) || ! array_key_exists($segment, $config)) {
                return $default;
            }

            $config = $config[$segment];
        }

        return $config;
    }

    protected function find(string $pluginName): ?Plugin
    {
        foreach ($this->plugins->all() as $plugin) {
            if ($plugin->name === $pluginName) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function projectConfig(string $pluginName): array
    {
        $config = $this->config->get('plugins.config.' . $pluginName, []);

        return is_array($config) ? $config : [];
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    protected function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && is_array($base[$key] ?? null)) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
