<?php

declare(strict_types=1);

namespace Mecha\Modules;

use LogicException;
use Psr\Container\ContainerInterface;

/** Provides a preset way to compile services from modules into a container and run all callbacks. */
class App
{
    protected Compiler $compiler;

    /** @var callable(array<string,callable>, array<string,callable>):ContainerInterface */
    protected $cntrFactory;

    protected ?ContainerInterface $cntr = null;

    /**
     * Construct a new modular application.
     *
     * @param iterable<iterable<mixed,callable>> $modules A list of modules, each being an iterable of services.
     * @param null|callable(array<string,callable>, array<string,callable>):ContainerInterface $cntrFactory A function
     *        that creates a PSR-11 container from factories and extensions. If omitted, an instance of the bundled
     *        {@link Container} class will be used.
     */
    public function __construct(iterable $modules = [], ?callable $cntrFactory = null)
    {
        $this->compiler = new Compiler();
        $this->cntrFactory = $cntrFactory ?? [static::class, 'defaultContainer'];

        if (!empty($modules)) {
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

    /** @return mixed */
    public function get(string $id)
    {
        if ($this->cntr === null) {
            throw new LogicException("Cannot get service '$id' before the app runs.");
        }

        return $this->cntr->get($id);
    }

    public function run(): void
    {
        $factories = $this->compiler->getFactories();
        $extensions = $this->compiler->getExtensions();

        $this->cntr = call_user_func($this->cntrFactory, $factories, $extensions);

        $this->compiler->runCallback($this->cntr);
    }

    /**
     * @param array<string,callable> $factories
     * @param array<string,callable> $extensions
     */
    public static function defaultContainer(array $factories, array $extensions): Container
    {
        return new Container($factories, $extensions);
    }
}
