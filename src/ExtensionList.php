<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

/** A callable class that stores lists of extensions and actions, and calls them all when the instance is invoked. */
class ExtensionList
{
    /** @var iterable<callable> */
    protected iterable $exts;

    /** @var iterable<callable> */
    protected iterable $actions;

    /**
     * Constructs a new function list.
     *
     * @param array<callable> $exts The list of extensions.
     * @param array<callable> $actions The list of actions.
     */
    public function __construct(array $exts = [], array $actions = [])
    {
        $this->exts = $exts;
        $this->actions = $actions;
    }

    /** @param callable $fn */
    public function addExtension(callable $fn): self
    {
        $this->exts[] = $fn;

        return $this;
    }

    /** @param callable $fn */
    public function addAction(callable $fn): self
    {
        $this->actions[] = $fn;

        return $this;
    }

    /**
     * @param mixed $prev
     * @param mixed[] $args
     * @return mixed
     */
    public function __invoke(ContainerInterface $c, $prev, ...$args)
    {
        $args = func_get_args();
        foreach ($this->exts as $ext) {
            $args[1] = call_user_func_array($ext, $args);
        }

        foreach ($this->actions as $action) {
            call_user_func_array($action, $args);
        }

        return $args[1];
    }
}
