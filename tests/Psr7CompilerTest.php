<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Container;
use Mecha\Modules\Psr7Compiler;
use PHPUnit\Framework\TestCase;
use function Mecha\Modules\factory;
use function Mecha\Modules\run;
use function Mecha\Modules\value;

class Psr7CompilerTest extends TestCase
{
    /** @covers Psr7Compiler::addModule */
    public function test_addModule_factories(): void
    {
        $foo = value('foo');
        $bar = factory(fn($foo) => "$foo!", ['foo']);

        $module = [
            'foo' => $foo,
            'bar' => $bar,
        ];

        $compiler = new Psr7Compiler();
        $compiler->addModule($module);

        $factories = $compiler->getFactories();
        $actions = $compiler->getActions();

        $this->assertArrayHasKey('foo', $factories);
        $this->assertArrayHasKey('bar', $factories);

        $this->assertSame($foo, $factories['foo']);
        $this->assertSame($bar, $factories['bar']);
    }

    /** @covers Psr7Compiler::addModule */
    public function test_addModule_callbacks(): void
    {
        $run1 = run(fn() => printf('hello '));
        $run2 = run(fn() => printf('world'));

        $module = [
            $run1,
            $run2,
        ];

        $compiler = new Psr7Compiler();
        $compiler->addModule($module);

        $factories = $compiler->getFactories();
        $actions = $compiler->getActions();

        $this->assertCount(2, $actions);
        $this->expectOutputString('hello world');

        $c = new Container($factories);
        foreach ($actions as $callback) {
            $callback($c);
        }
    }
}
