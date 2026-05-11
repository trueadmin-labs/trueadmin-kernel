<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Constant;

interface ErrorCodeInterface
{
    public function code(): string;

    public function message(?array $parameters = null): string;
}
