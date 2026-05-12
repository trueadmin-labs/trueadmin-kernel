<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http;

use TrueAdmin\Kernel\Context\Actor;

interface PermissionProviderInterface
{
    public function can(Actor $actor, string $permission): bool;
}
