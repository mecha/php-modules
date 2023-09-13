<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Generator;
use Psr\Container\ContainerInterface;

class App
{
    protected Container $container;

    /** @var array<string,callable(ContainerInterface):mixed*/
    protected array $factories = [];

    /** @var list<callable(ContainerInterface):void> */
    protected array $callbacks = [];

    public function __construct()
    {
        $this->container = new Container();
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
        foreach ($module as $key => $service) {
            $this->container->add($key, $service);

            if ($service->run !== null) {
                $this->callbacks[] = fn($c) => ($service->run)($c->get($key), $c);
            }
        }

        if ($module instanceof Generator) {
            $run = $module->getReturn();

            if (is_callable($run)) {
                $this->callbacks[] = $run;
            }
        }

        return $this;
    }

    public function run(): void
    {
        foreach ($this->callbacks as $callback) {
            $callback($this->container);
        }
    }
}
