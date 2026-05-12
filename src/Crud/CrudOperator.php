<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

enum CrudOperator: string
{
    case Eq = 'eq';
    case Ne = 'ne';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case Like = 'like';
    case In = 'in';
    case Between = 'between';
    case IsNull = 'is_null';
    case NotNull = 'not_null';

    public function sqlOperator(): string
    {
        return match ($this) {
            self::Eq => '=',
            self::Ne => '<>',
            self::Gt => '>',
            self::Gte => '>=',
            self::Lt => '<',
            self::Lte => '<=',
            default => $this->value,
        };
    }
}
