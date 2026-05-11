<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\OperationLog\Event;

final class OperationLogged
{
    public function __construct(
        public readonly string $module,
        public readonly string $action,
        public readonly string $remark = '',
        public readonly array $context = [],
    ) {
    }
}
