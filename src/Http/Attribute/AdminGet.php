<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AdminGet extends RouteMapping
{
    public function __construct(
        string $path,
        string $name = '',
        array $middleware = [],
        bool $deprecated = false,
    ) {
        parent::__construct($path, ['GET'], $name, $middleware, $deprecated);
    }
}
