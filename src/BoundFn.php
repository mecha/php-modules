<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

class BoundFn
{
    public $fn;
    public array $deps;

    /**
     * @param callable(mixed):mixed $fn
     * @param list<string|Service> $deps
     */
    public function __construct(callable $fn, array $deps)
    {
        $this->fn = $fn;
        $this->deps = $deps;
    }

    /**
     * Gets the dependencies.
     *
     * @return list<string|Service>
     */
    public function getDeps(): array
    {
        return $this->deps;
    }

    /**
     * Creates a copy with an added dependency.
     *
     * @param string|Servicie $dep The dependency to add.
     */
    public function addDep($dep): self
    {
        $clone = clone $this;
        $clone->deps[] = $dep;

        return $clone;
    }

    /**
     * Creates a copy with different dependencies.
     *
     * @param list<string|Service> $deps The new dependencies.
     */
    public function withDeps(array $deps): self
    {
        $clone = clone $this;
        $clone->deps = $deps;

        return $clone;
    }

    /**
     * @param mixed[] $args
     * @return mixed
     */
    public function __invoke(ContainerInterface $c, ...$args)
    {
        return call_user_func($this->fn, ...$args, ...resolveDeps($c, $this->deps));
    }
}
