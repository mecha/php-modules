<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Container;
use Mecha\Modules\Compiler;
use Mecha\Modules\Service;
use PHPUnit\Framework\TestCase;
use function Mecha\Modules\factory;
use function Mecha\Modules\run;
use function Mecha\Modules\value;

/** @covers Compiler */
class CompilerTest extends TestCase
{
    public function test_addModule_factories(): void
    {
        $foo = value('foo');
        $bar = factory(fn($foo) => "$foo!", ['foo']);

        $module = [
            'foo' => $foo,
            'bar' => $bar,
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $factories = $compiler->getFactories();

        $this->assertArrayHasKey('foo', $factories);
        $this->assertArrayHasKey('bar', $factories);

        $this->assertSame($foo, $factories['foo']);
        $this->assertSame($bar, $factories['bar']);
    }

    public function test_addModule_callbacks(): void
    {
        $run1 = run(fn() => printf('hello '));
        $run2 = run(fn() => printf('world'));

        $module = [
            $run1,
            $run2,
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $factories = $compiler->getFactories();
        $action = $compiler->getCallback();

        $this->assertIsCallable($action);
        $this->expectOutputString('hello world');

        $c = new Container($factories);
        $action($c);
    }

    public function test_getExtensions(): void
    {
        $module = [
            'msg' => fn() => 'hello',
            'foo' => (new Service(fn() =>'world'))->extends('msg', fn($msg, $foo) => "$msg $foo"),
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $extensions = $compiler->getExtensions();

        $this->assertArrayHasKey('msg', $extensions);
    }

    public function test_getCallback(): void
    {
        $compiler = new Compiler();
        $compiler->addModule([
            run(fn() => printf('hello ')),
            run(fn() => printf('world')),
        ]);

        $this->expectOutputString('hello world');

        $c = new Container($compiler->getFactories());
        $action = $compiler->getCallback();

        $action($c);
    }

    public function test_runCallback(): void
    {
        $compiler = new Compiler();
        $compiler->addModule([
            run(fn() => printf('hello ')),
            run(fn() => printf('world')),
        ]);

        $this->expectOutputString('hello world');

        $c = new Container($compiler->getFactories());
        $compiler->runCallback($c);
    }
}
