<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RouteMapping extends AbstractAnnotation
{
    /**
     * @param list<string> $methods
     */
    public function __construct(
        public readonly string $path,
        public readonly array $methods,
        public readonly string $name = '',
        public readonly array $middleware = [],
        public readonly bool $deprecated = false,
    ) {
    }
}
