<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Generator;
use Psr\Container\ContainerInterface;

/** Compiles modules into a list of factories, for use with a PSR7 container, and a list of callbacks. */
class Psr7Compiler
{
    /** @var array<string,callable(ContainerInterface,mixed):mixed> */
    protected array $factories = [];
    /** @var list<callable(ContainerInterface):void> */
    protected array $actions = [];

    /** 
     * Construct a new compiler.
     * @param iterable<iterable<int|string,Service> $modules
     */
    public function __construct(iterable $modules = [])
    {
        $this->addModules($modules);
    }

    /** @param iterable<iterable<int|string,Service> $modules */
    public function addModules(iterable $modules): self
    {
        foreach ($modules as $module) {
            $this->addModule($module);
        }

        return $this;
    }

    /** @param iterable<string,Service> $module */
    public function addModule(iterable $module): self
    {
        foreach ($module as $id => $service) {
            $this->addService($id, $service);
        }

        if ($module instanceof Generator) {
            $return = $module->getReturn();
            if (is_callable($return)) {
                $this->actions[] = $return;
            }
        }

        return $this;
    }

    /**
     * Adds a service.
     *
     * @param string|int|null $id The ID of the service. If not a string, a unique ID will be generated.
     * @param callable(ContainerInterface,mixed):mixed $service
     */
    protected function addService($id, callable $service): self
    {
        if (!is_string($id)) {
            $id = uniqid('anon');
        }

        if (array_key_exists($id, $this->factories)) {
            $curr = $this->factories[$id];
            $this->factories[$id] = function (ContainerInterface $c) use ($curr, $service) {
                return $service($c, $curr($c));
            };
        } else {
            $this->factories[$id] = $service;
        }

        if ($service instanceof Service && $service->action !== null) {
            $this->actions[] = fn (ContainerInterface $c) => call_user_func($service->action, $c->get($id), $c);
        }

        return $this;
    }

    /** @return array<string,callable(ContainerInterface,mixed):mixed> */
    public function getFactories(): array
    {
        return $this->factories;
    }

    /** @return list<callable(ContainerInterface):void> */
    public function getActions(): array
    {
        return $this->actions;
    }
}
