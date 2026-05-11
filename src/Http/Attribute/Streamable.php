<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Streamable extends AbstractAnnotation
{
    /**
     * @param list<string> $requestHeaders
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly string $completedMessage = '处理完成',
        public readonly array $requestHeaders = ['text/event-stream'],
    ) {
    }
}
