<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Generator;
use Psr\Container\ContainerInterface;

/** Compiles modules into a list of factories, for use with a PSR-11 container, and a list of callbacks. */
class Compiler
{
    /** @var array<string,callable(ContainerInterface,mixed):mixed> */
    protected array $factories = [];
    /** @var array<string,ExtensionList> */
    protected array $extensions = [];
    protected FunctionList $callback;

    /**
     * Construct a new compiler.
     * @param iterable<iterable<int|string,Service> $modules
     */
    public function __construct(iterable $modules = [])
    {
        $this->callback = new FunctionList();
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
                $this->callback->add($return);
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
                if ($extension instanceof BoundFn) {
                    $extension = $extension->addDep($id);
                }

                $this->extensions[$extId] ??= new ExtensionList();
                $this->extensions[$extId]->addExtension($extension);
            }

            foreach ($service->actions as $actId => $action) {
                if ($action instanceof BoundFn) {
                    $action = $action->addDep($id);
                }

                $this->extensions[$actId] ??= new ExtensionList();
                $this->extensions[$actId]->addAction($action);
            }

            foreach ($service->callbacks as $callback) {
                if ($callback instanceof BoundFn) {
                    $callback = $callback->addDep($id);
                }

                $this->callback->add($callback);
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

    /** @return callable(ContainerInterface):void */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function runCallback(ContainerInterface $c): void
    {
        call_user_func($this->callback, $c);
    }
}
