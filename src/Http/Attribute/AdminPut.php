<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AdminPut extends RouteMapping
{
    public function __construct(
        string $path,
        string $name = '',
        array $middleware = [],
        bool $deprecated = false,
    ) {
        parent::__construct($path, ['PUT'], $name, $middleware, $deprecated);
    }
}
