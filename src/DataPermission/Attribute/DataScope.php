<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission\Attribute;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DataScope extends AbstractAnnotation
{
    public function __construct(
        public readonly string $resource,
    ) {
    }
}
