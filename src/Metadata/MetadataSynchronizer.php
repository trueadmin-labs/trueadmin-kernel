<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Metadata;

use Hyperf\DbConnection\Db;

class MetadataSynchronizer
{
    public function __construct(
        private readonly InterfaceMetadataScanner $scanner,
        private readonly MetadataMenuRepositoryInterface $menus,
    ) {
    }

    public function sync(bool $force = false): array
    {
        $metadata = $this->scanner->scan();
        $now = date('Y-m-d H:i:s');
        $synced = ['menus' => 0, 'buttons' => 0, 'permissions' => 0];

        Db::transaction(function () use ($metadata, $now, $force, &$synced): void {
            $menuIds = [];
            $menus = [];
            foreach ($metadata['menus'] ?? [] as $menu) {
                $menus[$menu['code']] = $menu;
            }

            foreach (array_keys($menus) as $code) {
                if (! isset($menuIds[$code])) {
                    $menuIds[$code] = $this->syncMenu($code, $menus, $menuIds, $now, $force, []);
                    ++$synced['menus'];
                }
            }

            foreach ($menus as $menu) {
                if ((string) ($menu['permission'] ?? '') !== '') {
                    ++$synced['permissions'];
                }
                if ((string) ($menu['type'] ?? '') === 'button') {
                    ++$synced['buttons'];
                }
            }

            if ($force) {
                $this->menus->deleteCodeMenusExcept(array_keys($menus));
            }
        });

        return $synced;
    }

    protected function syncMenu(string $code, array $menus, array &$menuIds, string $now, bool $force, array $visiting): int
    {
        if (isset($menuIds[$code])) {
            return $menuIds[$code];
        }
        if (! isset($menus[$code])) {
            throw new \RuntimeException(sprintf('Menu [%s] is referenced but not defined.', $code));
        }
        if (isset($visiting[$code])) {
            $cycle = implode(' -> ', [...array_keys($visiting), $code]);
            throw new \RuntimeException(sprintf('Circular menu parent relationship detected: %s.', $cycle));
        }

        $menu = $menus[$code];
        $parentCode = (string) ($menu['parent'] ?? '');
        if ($parentCode !== '' && ! isset($menus[$parentCode])) {
            throw new \RuntimeException(sprintf('Menu [%s] references missing parent menu [%s].', $code, $parentCode));
        }

        $parentId = $parentCode !== ''
            ? $this->syncMenu($parentCode, $menus, $menuIds, $now, $force, [...$visiting, $code => true])
            : 0;

        return $menuIds[$code] = $this->menus->upsertMenu($menu, $parentId, $now, $force);
    }
}
