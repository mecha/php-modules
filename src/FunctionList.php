<?php

declare(strict_types=1);

namespace Mecha\Modules;

/** A callable class that stores a list of functions, and calls them all when it gets invoked. */
class FunctionList
{
    /** @var list<callable> */
    protected array $fns;

    /** @param list<callable> $fns */
    public function __construct(array $fns = [])
    {
        $this->fns = $fns;
    }

    /** @param callable $fn */
    public function add(callable $fn): self
    {
        $this->fns[] = $fn;

        return $this;
    }

    public function __invoke(): void
    {
        foreach ($this->fns as $fn) {
            call_user_func_array($fn, func_get_args());
        }
    }
}
