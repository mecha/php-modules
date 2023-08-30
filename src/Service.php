<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

/**
 * @type DepResolveFn = callable(): Generator<mixed>
 */
class Service
{
    /** @var callable(DepResolveFn,ContainerInterface,mixed): mixed */
    public $fn;
    public array $deps = [];

    /**
     * @param callable(DepResolveFn,ContainerInterface,mixed): mixed $fn
     * @param list<string|Service> $deps */
    public function __construct(callable $fn, array $deps = [])
    {
        $this->fn = $fn;
        $this->deps = $deps;
    }

    /** @param list<string|Service> $deps */
    public function withDeps(array $deps): self
    {
        $clone = clone $this;
        $clone->deps = $deps;
        return $clone;
    }

    public function prefixDeps(string $prefix): self
    {
        $newDeps = [];

        foreach ($this->deps as $dep) {
            if ($dep instanceof self) {
                $newDeps[] = $dep->prefixDeps($prefix);
            } elseif (is_string($dep)) {
                if ($dep[0] === '@') {
                    $newDeps[] = $dep;
                } else {
                    $newDeps[] = $prefix . $dep;
                }
            }
        }

        return $this->withDeps($newDeps);
    }

    /**
     * @param mixed $prev
     * @return mixed
     */
    public function __invoke(ContainerInterface $c, $prev = null)
    {
        $deps = self::resolveDeps($c, $this->deps);

        return call_user_func_array($this->fn, [$deps, $c, $prev]);
    }

    /**
     * @param iterable<string|Service> $deps
     * @return iterable<mixed>
     */
    public static function resolveDeps(ContainerInterface $c, iterable $deps): iterable
    {
        foreach ($deps as $dep) {
            if ($dep instanceof self) {
                yield $dep($c);
            } else {
                yield $c->get($dep);
            }
        }
    }
}
