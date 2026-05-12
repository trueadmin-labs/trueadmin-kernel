<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Database\Listener;

use Hyperf\Database\Events\TransactionCommitted;
use Hyperf\Database\Events\TransactionRolledBack;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use TrueAdmin\Kernel\Database\AfterCommitCallbacks;

#[Listener]
final class RunAfterCommitCallbacksListener implements ListenerInterface
{
    public function __construct(private readonly AfterCommitCallbacks $callbacks)
    {
    }

    public function listen(): array
    {
        return [
            TransactionCommitted::class,
            TransactionRolledBack::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof TransactionCommitted) {
            $this->callbacks->committed($event->connection);
            return;
        }

        if ($event instanceof TransactionRolledBack) {
            $this->callbacks->rolledBack($event->connection);
        }
    }
}
