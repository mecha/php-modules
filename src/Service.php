<?php

declare(strict_types=1);

namespace Mecha\Modules;

use LogicException;
use Psr\Container\ContainerInterface;

class Service
{
    /** @var callable(array<mixed>,mixed): mixed */
    public $factory;
    /** @var list<string|Service> */
    public array $deps = [];
    /** @var array<string,callable(ContainerInterface,mixed,mixed):mixed> */
    public array $extensions = [];
    /** @var list<Service> */
    public array $actions = [];

    /**
     * Construct a new service.
     *
     * @param callable(array<mixed>,mixed): mixed $factory A function that takes the deps and previous value.
     * @param list<string|Service> $deps List of dependency IDs or service instances.
     */
    public function __construct(callable $factory, array $deps = [])
    {
        $this->factory = $factory;
        $this->deps = $deps;
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
        $deps = resolveDeps($c, $this->deps);

        return call_user_func_array($this->factory, [$deps, $prev]);
    }
}
