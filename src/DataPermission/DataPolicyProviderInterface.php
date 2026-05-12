<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

use TrueAdmin\Kernel\Context\Actor;

interface DataPolicyProviderInterface
{
    /**
     * @return list<DataPolicyRule>
     */
    public function policiesFor(Actor $actor, string $resource): array;
}
