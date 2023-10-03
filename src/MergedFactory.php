<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

class MergedFactory
{
    /* @var callable(ContainerInterface):mixed */
    protected $factory;
    /* @var callable(ContainerInterface,mixed):mixed */
    protected $extension;

    /**
     * @param callable(ContainerInterface):mixed $factory
     * @param callable(ContainerInterface,mixed):mixed $extension
     */
    public function __construct(callable $factory, callable $extension)
    {
        $this->factory = $factory;
        $this->extension = $extension;
    }

    /** @return mixed */
    public function __invoke(ContainerInterface $c)
    {
        $result = call_user_func($this->factory, $c);
        $result = call_user_func($this->extension, $c, $result);

        return $result;
    }
}
