<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Service;

use TrueAdmin\Kernel\Constant\ErrorCode;
use TrueAdmin\Kernel\Exception\BusinessException;

abstract class AbstractService
{
    protected function assertUnique(bool $exists, string $field, string $reason = 'duplicated'): void
    {
        if ($exists) {
            throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, ['field' => $field, 'reason' => $reason]);
        }
    }

    /**
     * @param list<int> $expectedIds
     * @param list<int> $existingIds
     */
    protected function assertExistingIds(array $expectedIds, array $existingIds, string $field, string $reason): void
    {
        $expectedIds = array_values(array_unique(array_map('intval', $expectedIds)));
        $existingIds = array_values(array_unique(array_map('intval', $existingIds)));
        sort($expectedIds);
        sort($existingIds);

        if ($expectedIds !== $existingIds) {
            throw new BusinessException(ErrorCode::VALIDATION_FAILED, 422, ['field' => $field, 'reason' => $reason]);
        }
    }

    protected function notFound(string $resource, int|string $id): BusinessException
    {
        return new BusinessException(ErrorCode::NOT_FOUND, 404, ['resource' => $resource, 'id' => $id]);
    }
}
