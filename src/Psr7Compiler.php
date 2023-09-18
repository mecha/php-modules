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
    /** @var array<string,ExtensionList> */
    protected array $extensions = [];
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

        if ($service instanceof Service) {
            foreach ($service->extensions as $extId => $extension) {
                $this->extensions[$extId] ??= new ExtensionList();
                $this->extensions[$extId]->add($extension, $id);
            }

            foreach ($service->actions as $action) {
                $this->actions[] = fn (ContainerInterface $c) => $action($c)($c->get($id));
            }
        }

        return $this;
    }

    /** @return array<string,callable(ContainerInterface,mixed):mixed> */
    public function getFactories(): array
    {
        return $this->factories;
    }

    /** @return array<string,callable(ContainerInterface,mixed):mixed> */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /** @return list<callable(ContainerInterface):void> */
    public function getActions(): array
    {
        return $this->actions;
    }

    /** @return callable(ContainerInterface):void */
    public function getMergedAction(): callable
    {
        return function (ContainerInterface $c): void {
            foreach ($this->actions as $action) {
                $action($c);
            }
        };
    }
}
