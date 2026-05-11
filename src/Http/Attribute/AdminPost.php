<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AdminPost extends RouteMapping
{
    public function __construct(
        string $path,
        string $name = '',
        array $middleware = [],
        bool $deprecated = false,
    ) {
        parent::__construct($path, ['POST'], $name, $middleware, $deprecated);
    }
}
