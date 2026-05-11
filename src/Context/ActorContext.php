<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Context;

use Hyperf\Context\Context;
use RuntimeException;

final class ActorContext
{
    private const PRINCIPAL_KEY = 'trueadmin.actor.principal';
    private const OPERATOR_KEY = 'trueadmin.actor.operator';

    public static function setPrincipal(Actor $actor): void
    {
        Context::set(self::PRINCIPAL_KEY, $actor);
    }

    public static function setOperator(Actor $actor): void
    {
        Context::set(self::OPERATOR_KEY, $actor);
    }

    public static function principal(): ?Actor
    {
        $actor = Context::get(self::PRINCIPAL_KEY);

        return $actor instanceof Actor ? $actor : null;
    }

    public static function operator(): ?Actor
    {
        $actor = Context::get(self::OPERATOR_KEY);

        return $actor instanceof Actor ? $actor : null;
    }

    public static function requirePrincipal(): Actor
    {
        return self::principal() ?? throw new RuntimeException('Principal actor is missing.');
    }

    public static function requireOperator(): Actor
    {
        return self::operator() ?? throw new RuntimeException('Operator actor is missing.');
    }

    public static function set(Actor $principal, ?Actor $operator = null): void
    {
        self::setPrincipal($principal);
        self::setOperator($operator ?? $principal);
    }

    public static function clear(): void
    {
        Context::destroy(self::PRINCIPAL_KEY);
        Context::destroy(self::OPERATOR_KEY);
    }

    public static function runAsSystem(callable $callback, ?string $reason = null): mixed
    {
        return self::runAs(Actor::system($reason), $callback);
    }

    public static function runAs(Actor $actor, callable $callback, ?Actor $operator = null): mixed
    {
        $previousPrincipal = self::principal();
        $previousOperator = self::operator();

        self::set($actor, $operator);

        try {
            return $callback();
        } finally {
            self::restore($previousPrincipal, $previousOperator);
        }
    }

    private static function restore(?Actor $principal, ?Actor $operator): void
    {
        self::clear();

        if ($principal instanceof Actor) {
            self::setPrincipal($principal);
        }

        if ($operator instanceof Actor) {
            self::setOperator($operator);
        }
    }
}
