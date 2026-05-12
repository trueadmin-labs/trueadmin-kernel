<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database\Seeder;

use Hyperf\Database\Seeders\Seed;
use Hyperf\Database\Seeders\Seeder;
use InvalidArgumentException;

use function Hyperf\Support\make;

class NamespacedSeed extends Seed
{
    public function resolve(string $file): object
    {
        $class = $this->getSeederName($file);
        if (! class_exists($class)) {
            return parent::resolve($file);
        }

        if (! is_subclass_of($class, Seeder::class)) {
            throw new InvalidArgumentException(sprintf('Seeder must extend %s: %s', Seeder::class, $class));
        }

        return make($class);
    }

    public function getSeederName($path): string
    {
        if (! is_string($path) || ! is_file($path)) {
            return parent::getSeederName($path);
        }

        return $this->classFromFile($path) ?? parent::getSeederName($path);
    }

    protected function classFromFile(string $path): ?string
    {
        $contents = file_get_contents($path);
        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $namespace = '';
        $tokens = token_get_all($contents);
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readName($tokens, $i + 1);
                continue;
            }

            if ($token[0] === T_CLASS) {
                $class = $this->readClassName($tokens, $i + 1);
                if ($class === null) {
                    return null;
                }

                return $namespace === '' ? $class : $namespace . '\\' . $class;
            }
        }

        return null;
    }

    protected function readName(array $tokens, int $start): string
    {
        $name = '';
        $count = count($tokens);
        for ($i = $start; $i < $count; ++$i) {
            $token = $tokens[$i];
            if ($token === ';' || $token === '{') {
                break;
            }

            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $name .= $token[1];
            }
        }

        return $name;
    }

    protected function readClassName(array $tokens, int $start): ?string
    {
        $count = count($tokens);
        for ($i = $start; $i < $count; ++$i) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }
        }

        return null;
    }
}
