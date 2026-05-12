<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Crud;

enum CrudSortOrder: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
