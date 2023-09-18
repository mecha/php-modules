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
     * @param string $id The ID of the service to extend.
     * @param callable(mixed,mixed):mixed $fn A function that returns the new value and accepts the following args:
     *        2. The previous value
     *        3. The service value
     *        4. The dependencies.
     * @param array<string|Service> $deps The dependencies of the extension.
     */
    public function extends(string $id, callable $fn, iterable $deps = []): Service
    {
        $clone = clone $this;
        $clone->extensions[$id] = extend($fn, $deps);

        return $clone;
    }

    /**
     * Extend an action service with an extension that calls it with the service's value and returns it unchanged.
     *
     * @param string $actionId The ID of the action service to extend and use.
     * @return Service The extended action service.
     */
    public function use(string $actionId): self
    {
        $clone = clone $this;
        $clone->extensions[$actionId] = extend(function ($action, $self) {
            if (is_callable($action)) {
                $action($self);
            }

            return $action;
        }, []);

        return $clone;
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
