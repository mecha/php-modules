<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

class ExtensionList
{
    /** @var list<callable(ContainerInterface,mixed):mixed> */
    protected array $fns;

    public function __construct()
    {
        $this->fns = [];
    }

    /**
     * Adds an extension to the list.
     *
     * @param list<callable(ContainerInterface,mixed):mixed> $fn The extension function.
     * @param string|null $id Optional ID of the "self" service, which gets added as a first dependency.
     */
    public function add(callable $fn, ?string $id = null): void
    {
        if ($id !== null && $fn instanceof Service) {
            $fn = clone $fn;
            array_unshift($fn->deps, $id);
        }

        $this->fns[] = $fn;
    }

    /**
     * @param mixed $prev
     * @return mixed
     */
    public function __invoke(ContainerInterface $c, $prev)
    {
        foreach ($this->fns as $fn) {
            $prev = $fn($c, $prev);
        }

        return $prev;
    }
}
