<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Support;

use Hyperf\DbConnection\Model\Model;

final class TreeHelper
{
    public function level(?Model $parent): int
    {
        return $parent === null ? 1 : (int) $parent->getAttribute('level') + 1;
    }

    public function path(?Model $parent): string
    {
        if ($parent === null) {
            return '';
        }

        $parentPath = rtrim((string) $parent->getAttribute('path'), ',');

        return $parentPath . ',' . (int) $parent->getAttribute('id') . ',';
    }

    public function isDescendant(Model $node, int $ancestorId): bool
    {
        return str_contains((string) $node->getAttribute('path'), ',' . $ancestorId . ',');
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    public function build(array $items, string $idKey = 'id', string $parentKey = 'parentId', string $childrenKey = 'children'): array
    {
        $nodes = [];
        foreach ($items as $item) {
            $item[$childrenKey] = [];
            $nodes[(int) $item[$idKey]] = $item;
        }

        $tree = [];
        foreach ($nodes as &$node) {
            $parentId = (int) $node[$parentKey];
            if ($parentId > 0 && isset($nodes[$parentId])) {
                $nodes[$parentId][$childrenKey][] = &$node;
                continue;
            }

            $tree[] = &$node;
        }
        unset($node);

        return $this->withoutEmptyChildren($tree, $childrenKey);
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    private function withoutEmptyChildren(array $items, string $childrenKey): array
    {
        return array_map(function (array $item) use ($childrenKey): array {
            $item[$childrenKey] = $this->withoutEmptyChildren($item[$childrenKey], $childrenKey);
            if ($item[$childrenKey] === []) {
                unset($item[$childrenKey]);
            }

            return $item;
        }, $items);
    }
}
