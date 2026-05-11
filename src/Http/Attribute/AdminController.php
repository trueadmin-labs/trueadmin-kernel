<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class AdminController extends AbstractAnnotation
{
    public function __construct(
        public readonly string $path = '',
        public readonly string $title = '',
        public readonly array $middleware = [],
        public readonly array $tags = [],
    ) {
    }
}
