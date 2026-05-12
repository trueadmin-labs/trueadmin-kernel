<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database\Listener;

use TrueAdmin\Kernel\Database\ModuleMigrationLocator;
use Hyperf\Database\Migrations\Migrator;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

#[Listener]
final class RegisterMigrationPathsListener implements ListenerInterface
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly ModuleMigrationLocator $locator,
    ) {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        foreach ($this->locator->migrationPaths() as $path) {
            $this->migrator->path($path);
        }
    }
}
