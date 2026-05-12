<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database\Command;

use TrueAdmin\Kernel\Database\ModuleMigrationLocator;
use Hyperf\Command\Command;

final class TrueAdminMigrationPathsCommand extends Command
{
    public function __construct(private readonly ModuleMigrationLocator $locator)
    {
        parent::__construct('trueadmin:migration-paths');
        $this->setDescription('List TrueAdmin module migration and seeder paths');
    }

    public function handle(): int
    {
        $this->line('Migration paths:');
        foreach ($this->locator->migrationPaths() as $path) {
            $this->line(' - ' . $path);
        }

        $this->line('Seeder paths:');
        foreach ($this->locator->seederPaths() as $path) {
            $this->line(' - ' . $path);
        }

        return self::SUCCESS;
    }
}
