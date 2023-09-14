<?php

declare(strict_types=1);

namespace Mecha\Modules;

/**
 * Provides a preset way to compile services from modules into a container and run all callbacks.
 */
class App
{
    protected Psr7Compiler $compiler;

    /** @var callable(array<string,callable>):ContainerInterface */
    protected $cntrFactory;

    /**
     * Construct a new modular application.
     *
     * @param iterable<iterable<mixed,callable>> $modules A list of modules, each being an iterable of services.
     * @param null|callable(array<string,callable>):ContainerInterface $cntrFactory A function that creates a PSR-7
     *        container from a list of services. If omitted, the bundled {@link Container} class will be used.
     */
    public function __construct(iterable $modules = [], ?callable $cntrFactory = null)
    {
        $this->compiler = new Psr7Compiler();
        $this->cntrFactory = $cntrFactory ?? fn (array $factories) => new Container($factories);

        if (count($modules) > 0) {
            $this->addModules($modules);
        }
    }

    /** @param iterable<iterable<int|string,Service> $modules */
    public function addModules(iterable $modules): self
    {
        $this->compiler->addModules($modules);

        return $this;
    }

    /** @param iterable<string,Service> $module */
    public function addModule(iterable $module): self
    {
        $this->compiler->addModule($module);

        return $this;
    }

    public function run(): void
    {
        $cntr = call_user_func($this->cntrFactory, $this->compiler->getFactories());

        foreach ($this->compiler->getActions() as $callback) {
            $callback($cntr);
        }
    }
}
