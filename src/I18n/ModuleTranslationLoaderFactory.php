<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\I18n;

use TrueAdmin\Kernel\Plugin\PluginRepository;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Support\Filesystem\Filesystem;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

final class ModuleTranslationLoaderFactory
{
    public function __invoke(ContainerInterface $container): ModuleTranslationLoader
    {
        $config = $container->get(ConfigInterface::class);
        $files = $container->get(Filesystem::class);
        $plugins = $container->has(PluginRepository::class) ? $container->get(PluginRepository::class) : null;
        $paths = $config->get('translation.paths', []);
        $paths = is_array($paths) ? $paths : [];
        array_unshift($paths, dirname(__DIR__, 2) . '/resources/lang');

        return make(ModuleTranslationLoader::class, [
            'files' => $files,
            'paths' => array_values(array_unique($paths)),
            'plugins' => $plugins,
        ]);
    }
}
