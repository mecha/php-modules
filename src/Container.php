<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Exception;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    /** @var array<string,callable(ContainerInterface):mixed> */
    protected array $factories = [];
    /** @var array<string,mixed> */
    protected array $cache = [];
    /** @var list<string> */
    protected array $stack = [];

    /**
     * Construct a new container.
     *
     * @param iterable<string,callable(ContainerInterface):mixed> $factories The factories.
     */
    public function __construct(iterable $factories = [])
    {
        $this->factories = is_array($factories) ? $factories : iterator_to_array($factories);
    }

    /** @return mixed */
    public function get($id)
    {
        if (array_search($id, $this->stack) !== false) {
            throw new LogicException('Circular dependency detected: ' . $this->getStackAsString());
        }

        array_push($this->stack, $id);

        if (!array_key_exists($id, $this->factories)) {
            throw new class ($id) extends Exception implements NotFoundExceptionInterface {
                public function __construct(string $id)
                {
                    parent::__construct("Service \"$id\" not found. Stack: " . $this->getStackAsString());
                }
            };
        }

        $result = $this->cache[$id] ??= $this->factories[$id]($this);

        array_pop($this->stack);

        return $result;
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->factories);
    }

    protected function getStackAsString(): string
    {
        return implode(' -> ', array_keys($this->stack));
    }
}
