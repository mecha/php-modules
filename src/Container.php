<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    protected array $services = [];
    protected array $cache = [];

    /** @param callable(ContainerInterface):mixed $service */
    public function add(string $id, $service): self
    {
        if (array_key_exists($id, $this->services)) {
            $prev = $this->services[$id];

            $this->services[$id] = function (ContainerInterface $c) use ($prev, $service) {
                return $service($c, $prev($c));
            };
        } else {
            $this->services[$id] = $service;
        }

        return $this;
    }

    /** @return mixed */
    public function get($id)
    {
        if (!array_key_exists($id, $this->services)) {
            throw new class ($id) extends Exception implements NotFoundExceptionInterface {
                public function __construct(string $id)
                {
                    parent::__construct("Service not found: $id");
                }
            };
        }

        // TODO: Maybe add circular dependency detection
        return $this->cache[$id] ??= $this->services[$id]($this);
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
