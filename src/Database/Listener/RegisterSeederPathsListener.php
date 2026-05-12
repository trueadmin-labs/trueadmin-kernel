<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database\Listener;

use TrueAdmin\Kernel\Database\ModuleMigrationLocator;
use Hyperf\Database\Seeders\Seed;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

#[Listener]
final class RegisterSeederPathsListener implements ListenerInterface
{
    public function __construct(
        private readonly Seed $seed,
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
        foreach ($this->locator->seederPaths() as $path) {
            $this->seed->path($path);
        }
    }
}
