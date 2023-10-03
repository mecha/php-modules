<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Container;
use Mecha\Modules\Compiler;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\bind;
use function Mecha\Modules\callback;
use function Mecha\Modules\invoke;
use function Mecha\Modules\run;
use function Mecha\Modules\value;

class RunTest extends TestCase
{
    /** @covers run */
    public function test_run(): void
    {
        $module = [
            run(bind(fn($d1, $d2) => printf("%s %s\n", $d1, $d2), ['d1', 'd2'])),
            'd1' => value('hello'),
            'd2' => value('world')
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);
        $c = new Container($compiler->getFactories());

        $this->expectOutputString("hello world\n");

        $compiler->getCallback()($c);
    }

    /** @covers run Service::runs */
    public function test_run_service(): void
    {
        $compiler = new Compiler();
        $compiler->addModule([
            'foo' => value('foo'),
            'bar' => value('bar')
                    ->runs(bind(fn ($foo, $self) => printf('%s%s', $foo, $self), ['foo']))
                    ->runs(fn () => printf('test')),
        ]);

        $c = new Container($compiler->getFactories());

        $this->expectOutputString('foobartest');

        $compiler->getCallback()($c);
    }

    /** @covers run, invoke */
    public function test_run_invoke(): void
    {
        $compiler = new Compiler();
        $compiler->addModule([
            'init' => callback(fn($name) => printf('Initializing %s...', $name)),
            'name' => value('engines'),
            run(invoke('init', ['name'])),
        ]);

        $c = new Container($compiler->getFactories());

        $this->expectOutputString('Initializing engines...');

        $compiler->getCallback()($c);
    }

    /** @covers run, invoke, Service::runs */
    public function test_run_invoke_service(): void
    {
        $compiler = new Compiler();
        $compiler->addModule([
            'init' => callback(fn($name) => printf('Initializing %s...', $name)),
            'foo' => value('foo')->runs(invoke('init')),
        ]);

        $c = new Container($compiler->getFactories());

        $this->expectOutputString('Initializing foo...');

        $compiler->getCallback()($c);
    }

    public function suck_my_balls() :void
    {
        $module = [
            'foo' => value('foo')->runs(bind(fn($foo) => printf($foo))),
        ];

        $compiler = new Compiler([$module]);
        $c = new Container($compiler->getFactories());

        $this->expectOutputString('foo');

        $compiler->runCallback($c);
    }
}
