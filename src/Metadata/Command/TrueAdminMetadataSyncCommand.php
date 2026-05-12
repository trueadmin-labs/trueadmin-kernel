<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Metadata\Command;

use TrueAdmin\Kernel\Metadata\MetadataSynchronizer;
use Hyperf\Command\Command;
use Symfony\Component\Console\Input\InputOption;

final class TrueAdminMetadataSyncCommand extends Command
{
    public function __construct(private readonly MetadataSynchronizer $sync)
    {
        parent::__construct('trueadmin:metadata-sync');
        $this->setDescription('Sync TrueAdmin interface metadata to database');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite code-defined menu structure.');
    }

    public function handle(): int
    {
        $synced = $this->sync->sync((bool) $this->input->getOption('force'));
        $this->info(sprintf(
            'Metadata synced: menus=%d, permissions=%d, buttons=%d',
            $synced['menus'],
            $synced['permissions'],
            $synced['buttons'],
        ));

        return self::SUCCESS;
    }
}
