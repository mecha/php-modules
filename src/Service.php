<?php

declare(strict_types=1);

namespace Mecha\Modules;

use LogicException;
use Psr\Container\ContainerInterface;

/**
 * @type DepResolveFn = callable(): Generator<mixed>
 */
class Service
{
    /** @var callable(DepResolveFn,ContainerInterface,mixed): mixed */
    public $factory;
    /** @var list<string|Service> */
    public array $deps = [];
    /** @var list<Service> */
    public $actions = [];

    /**
     * @param callable(DepResolveFn,ContainerInterface,mixed): mixed $factory
     * @param list<string|Service> $deps
     * @param list<Service> $actions
     */
    public function __construct(callable $factory, array $deps = [], array $actions = [])
    {
        $this->factory = $factory;
        $this->deps = $deps;
        $this->actions = $actions;
    }

    /** @param list<string|Service> $deps */
    public function withDeps(array $deps): self
    {
        $clone = clone $this;
        $clone->deps = $deps;
        return $clone;
    }

    public function prefixDeps(string $prefix): self
    {
        $newDeps = [];

        foreach ($this->deps as $dep) {
            if ($dep instanceof self) {
                $newDeps[] = $dep->prefixDeps($prefix);
            } elseif (is_string($dep)) {
                if ($dep[0] === '@') {
                    $newDeps[] = substr($dep, 1);
                } else {
                    $newDeps[] = $prefix . $dep;
                }
            }
        }

        $clone = $this->withDeps($newDeps);

        $newActions = [];
        foreach ($clone->actions as $action) {
            $newActions[] = $action->prefixDeps($prefix);
        }

        $clone->actions = $newActions;

        return $clone;
    }

    /** @param callable(mixed,ContainerInterface): void $action */
    public function then(callable $action, iterable $deps = []): self
    {
        $clone = clone $this;
        $clone->actions[] = callback($action, $deps);

        return $clone;
    }

    public function thenUse(string $id): self
    {
        return $this->then(function ($value, $action) use ($id) {
            if (is_callable($action)) {
                call_user_func($action, $value);
            } else {
                throw new LogicException("Cannot use non-callable service \"$id\" as a run action.");
            }
        }, [$id]);
    }

    /**
     * @param mixed $prev
     * @return mixed
     */
    public function __invoke(ContainerInterface $c, $prev = null)
    {
        $deps = self::resolveDeps($c, $this->deps);

        return call_user_func_array($this->factory, [$deps, $c, $prev]);
    }

    /**
     * @param iterable<string|Service> $deps
     * @return list<mixed>
     */
    public static function resolveDeps(ContainerInterface $c, iterable $deps): array
    {
        $result = [];

        foreach ($deps as $dep) {
            if ($dep instanceof self) {
                $result[] = $dep($c);
            } else {
                $result[] = $c->get($dep);
            }
        }

        return $result;
    }
}
