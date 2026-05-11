<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\DataPermission;

enum ScopeType: string
{
    case ALL = 'all';
    case DEPARTMENT = 'department';
    case DEPARTMENT_AND_CHILDREN = 'department_and_children';
    case CREATED_BY = 'created_by';
    case DEPARTMENT_CREATED_BY = 'department_created_by';
    case SELF = 'self';
}
