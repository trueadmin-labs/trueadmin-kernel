<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Attribute;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Permission extends AbstractAnnotation
{
    /**
     * @param list<string> $anyOf
     * @param list<string> $allOf
     */
    public function __construct(
        public readonly string $code = '',
        public readonly string $title = '',
        public readonly string $group = '',
        public readonly bool $public = false,
        public readonly array $anyOf = [],
        public readonly array $allOf = [],
    ) {
    }

    public function mode(): string
    {
        if ($this->public) {
            return 'public';
        }

        $hasCode = $this->code !== '';
        $hasAnyOf = $this->anyOf !== [];
        $hasAllOf = $this->allOf !== [];
        $enabledModes = array_filter([$hasCode, $hasAnyOf, $hasAllOf]);

        if (count($enabledModes) !== 1) {
            throw new InvalidArgumentException('Permission must use exactly one of code, anyOf, or allOf.');
        }

        return match (true) {
            $hasAnyOf => 'anyOf',
            $hasAllOf => 'allOf',
            default => 'single',
        };
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        $codes = match ($this->mode()) {
            'public' => [],
            'anyOf' => $this->anyOf,
            'allOf' => $this->allOf,
            default => [$this->code],
        };

        $codes = array_values(array_unique(array_map(
            static fn (mixed $code): string => trim((string) $code),
            $codes,
        )));

        if ($this->mode() !== 'public' && in_array('', $codes, true)) {
            throw new InvalidArgumentException('Permission codes must not be empty.');
        }

        return $codes;
    }
}
