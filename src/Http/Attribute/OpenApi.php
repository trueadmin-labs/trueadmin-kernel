<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class OpenApi extends AbstractAnnotation
{
    public function __construct(
        public readonly string $summary = '',
        public readonly string $description = '',
        public readonly ?string $request = null,
        public readonly ?string $response = null,
        public readonly array $tags = [],
        public readonly array $security = [],
        public readonly array $errorCodes = [],
        public readonly bool $deprecated = false,
    ) {
    }
}
