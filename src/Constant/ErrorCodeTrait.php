<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Constant;

use Hyperf\Constants\EnumConstantsTrait;

trait ErrorCodeTrait
{
    use EnumConstantsTrait;

    public function code(): string
    {
        return $this->value;
    }

    public function message(?array $parameters = null): string
    {
        return $this->getMessage($parameters);
    }
}
