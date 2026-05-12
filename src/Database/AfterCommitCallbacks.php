<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database;

use Closure;
use Hyperf\Context\Context;
use Hyperf\Database\ConnectionInterface;
use Hyperf\DbConnection\Db;
use Throwable;

final class AfterCommitCallbacks
{
    private const CONTEXT_KEY = 'trueadmin.database.after_commit_callbacks';

    public function run(Closure $callback): mixed
    {
        $connection = Db::connection();
        $level = $connection->transactionLevel();
        if ($level === 0) {
            return $callback();
        }

        $id = $this->connectionId($connection);
        Context::override(self::CONTEXT_KEY, function (?array $callbacks) use ($callback, $id, $level): array {
            $callbacks ??= [];
            $callbacks[$id] ??= [];
            $callbacks[$id][] = ['level' => $level, 'callback' => $callback];

            return $callbacks;
        });

        return null;
    }

    public function committed(ConnectionInterface $connection): void
    {
        if ($connection->transactionLevel() > 0) {
            return;
        }

        $id = $this->connectionId($connection);
        $callbacks = Context::get(self::CONTEXT_KEY, []);
        $connectionCallbacks = $callbacks[$id] ?? [];
        unset($callbacks[$id]);
        $this->store($callbacks);

        foreach ($connectionCallbacks as $item) {
            $item['callback']();
        }
    }

    public function rolledBack(ConnectionInterface $connection): void
    {
        $id = $this->connectionId($connection);
        $level = $connection->transactionLevel();
        $callbacks = Context::get(self::CONTEXT_KEY, []);
        if (! isset($callbacks[$id])) {
            return;
        }

        $callbacks[$id] = array_values(array_filter(
            $callbacks[$id],
            static fn (array $item): bool => (int) $item['level'] <= $level,
        ));
        if ($callbacks[$id] === []) {
            unset($callbacks[$id]);
        }

        $this->store($callbacks);
    }

    /**
     * @param array<string, list<array{level: int, callback: Closure}>> $callbacks
     */
    private function store(array $callbacks): void
    {
        if ($callbacks === []) {
            Context::destroy(self::CONTEXT_KEY);
            return;
        }

        Context::set(self::CONTEXT_KEY, $callbacks);
    }

    private function connectionId(ConnectionInterface $connection): string
    {
        try {
            if (is_callable([$connection, 'getName'])) {
                return 'name:' . (string) $connection->getName();
            }
        } catch (Throwable) {
        }

        return 'object:' . spl_object_id($connection);
    }
}
