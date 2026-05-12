<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\I18n;

use TrueAdmin\Kernel\Plugin\PluginRepository;
use Hyperf\Contract\TranslatorLoaderInterface;
use Hyperf\Support\Filesystem\Filesystem;

class ModuleTranslationLoader implements TranslatorLoaderInterface
{
    private array $namespaces = [];

    private array $jsonPaths = [];

    /**
     * @param list<string> $paths
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly array $paths,
        private readonly ?PluginRepository $plugins = null,
    ) {
    }

    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        if ($group === '*') {
            return $this->loadJsonPaths($locale);
        }

        if ($namespace !== null && $namespace !== '*') {
            return $this->loadNamespaced($locale, $group, $namespace);
        }

        return $this->loadGroup($locale, $group, $this->languagePaths());
    }

    public function addNamespace(string $namespace, string $hint)
    {
        $this->namespaces[$namespace] = $hint;
    }

    public function addJsonPath(string $path)
    {
        $this->jsonPaths[] = $path;
    }

    public function namespaces(): array
    {
        return $this->namespaces;
    }

    protected function loadNamespaced(string $locale, string $group, string $namespace): array
    {
        $path = $this->namespaces[$namespace] ?? null;
        if (! is_string($path)) {
            return [];
        }

        return $this->loadGroup($locale, $group, [$path]);
    }

    /**
     * @param list<string> $paths
     */
    protected function loadGroup(string $locale, string $group, array $paths): array
    {
        $lines = [];
        foreach ($paths as $path) {
            $file = $path . '/' . $locale . '/' . $group . '.php';
            if ($this->files->exists($file)) {
                $lines = array_replace_recursive($lines, $this->files->getRequire($file));
            }
        }

        return $lines;
    }

    protected function loadJsonPaths(string $locale): array
    {
        $lines = [];
        foreach ([...$this->jsonPaths, ...$this->languagePaths()] as $path) {
            $file = $path . '/' . $locale . '.json';
            if ($this->files->exists($file)) {
                $decoded = json_decode($this->files->get($file), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $lines = array_replace_recursive($lines, $decoded);
                }
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function languagePaths(): array
    {
        $paths = $this->paths;

        foreach ($this->plugins?->enabled() ?? [] as $plugin) {
            $languagePath = $plugin->languagePath();
            if ($languagePath !== null) {
                $paths[] = $languagePath;
            }
        }

        return array_values(array_unique(array_filter($paths, static fn (string $path): bool => is_dir($path))));
    }
}
