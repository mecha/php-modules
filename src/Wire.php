<?php

declare(strict_types=1);

namespace Mecha\Modules;

/**
 * An object that stores a function and a list of inputs.
 *
 * When triggered with a subject value, the function will be called with the subject and each input.
 */
class Wire
{
    public $fn;
    public array $inputs;

    /**
     * @param callable(ContianerInterface,mixed,mixed):void $fn A function that takes the DI container, the subject,
     *        and an input value. The return value of the function is ignored.
     * @param list<mixed> $inputs Initial list of input values.
     */
    public function __construct(callable $fn, array $inputs = [])
    {
        $this->fn = $fn;
        $this->inputs = $inputs;
    }

    /** @param mixed $input */
    public function addInput($input): void
    {
        $this->inputs[] = $input;
    }

    /**
     * @param mixed $subject
     * @param list<mixed> $deps
     */
    public function trigger($subject, array $deps = []): void
    {
        foreach ($this->inputs as $input) {
            call_user_func_array($this->fn, [$subject, $input, ...$deps]);
        }
    }
}
