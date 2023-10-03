<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

class Service
{
    /** @var callable(array<mixed>,mixed): mixed */
    public $definition;
    /** @var array<string,callable(ContainerInterface,mixed,mixed):mixed> */
    public array $extensions = [];
    /** @var array<string,callable(ContainerInterface,mixed,mixed):void> */
    public array $actions = [];
    /** @var list<callable(ContainerInterface)> */
    public array $callbacks = [];

    /**
     * Construct a new service.
     *
     * @param callable(array<mixed>,mixed):mixed $definition A function that takes the deps and previous value.
     */
    public function __construct(callable $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Runs the service definition.
     *
     * @param ContainerInterface $c The DI container.
     * @param mixed[] $args Any other arguments that are passed to the service definition.
     * @return mixed The service value.
     */
    public function __invoke(ContainerInterface $c, ...$args)
    {
        return call_user_func_array($this->definition, func_get_args());
    }

    /**
     * Adds a callback as a run action for the service.
     *
     * @param callable(ContainerInterface): void $callback
     * @return self The new service.
     */
    public function runs(callable $callback): self
    {
        $clone = clone $this;
        $clone->callbacks[] = $callback;

        return $clone;
    }

    /**
     * Adds a bound callback as a run action for the service.
     *
     * @param callable(mixed): void $callback
     * @return self The new service.
     */
    public function then(callable $callback): self
    {
        return $this->runs(bind($callback));
    }

    /**
     * Adds an extension to the service.
     *
     * @param string $id The ID of the service to extend.
     * @param callable(mixed,mixed):mixed $fn A function that takes a DI container and the previous value and returns
     *        the new value. If the function is a {@link BoundFn}, it will have the service's own ID appended as a
     *        dependency. This adds the service's own value as the last argument for the extension function.
     * @return self The new service.
     */
    public function extends(string $id, callable $fn): Service
    {
        $clone = clone $this;
        $clone->extensions[$id] = $fn;

        return $clone;
    }

    /**
     * Adds an action to another service.
     *
     * @param string $id The ID of the service to add the action to.
     * @param callable(ContainerInterface,mixed):void $fn A function that takes a DI container and the other service's
     *        value. If this is a bound function, it will also recieve its own service's value.
     */
    public function on(string $id, callable $fn): Service
    {
        $clone = clone $this;
        $clone->actions[$id] = $fn;
        return $clone;
    }

    /**
     * Extends an action with an extension that calls the action with the service's value and then returns the
     * unmodified action.
     *
     * The service with the given $actionId must be a {@link BoundFn}. Typically, actions are created using the
     * {@link \Mecha\Modules\action} function.
     *
     * @param string $actionId The ID of the action service to extend and use.
     * @return Service The extended action service.
     */
    public function calls(string $actionId): self
    {
        return $this->extends($actionId, bind(function ($prev, ...$args) {
            $prev(...$args);
            return $prev;
        }));
    }

    /**
     * Connects the service to a wire.
     * This is equivalent to adding an action to the wire and adding the service to as a wire input.
     *
     * @param string $wireId The ID of the wire to connect to.
     */
    public function wire(string $wireId): self
    {
        return $this->on($wireId, bind(fn (Wire $wire, $self) => $wire->addInput($self)));
    }

    /**
     * Creates an extension that replaces the original service value with the one from this service.
     *
     * @param string $id The ID of the service to replace.
     */
    public function replaces(string $id): self
    {
        return $this->extends($id, bind(fn ($prev, $self) => $self));
    }
}
