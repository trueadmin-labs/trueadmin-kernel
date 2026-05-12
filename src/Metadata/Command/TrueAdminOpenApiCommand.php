<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Metadata\Command;

use TrueAdmin\Kernel\Metadata\OpenApiDocumentBuilder;
use Hyperf\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class TrueAdminOpenApiCommand extends Command
{
    public function __construct(private readonly OpenApiDocumentBuilder $builder)
    {
        parent::__construct('trueadmin:openapi');
        $this->setDescription('Export TrueAdmin OpenAPI document');
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output JSON path.', 'docs/openapi/openapi.json');
    }

    public function handle(): int
    {
        $output = (string) $this->input->getOption('output');
        $path = $this->resolvePath($output);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error(sprintf('Unable to create output directory [%s].', $directory));

            return self::FAILURE;
        }

        $json = json_encode(
            $this->builder->build(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        file_put_contents($path, $json . PHP_EOL);
        $this->info(sprintf('OpenAPI exported: %s', $path));

        return self::SUCCESS;
    }

    protected function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return BASE_PATH . '/' . ltrim($path, '/');
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }
}
