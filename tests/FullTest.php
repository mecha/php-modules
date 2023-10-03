<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Container;
use Mecha\Modules\Compiler;
use PHPUnit\Framework\TestCase;

use SplStack;
use function Mecha\Modules\factory;
use function Mecha\Modules\instance;
use function Mecha\Modules\run;
use function Mecha\Modules\value;
use function Mecha\Modules\constValue;
use function Mecha\Modules\wire;

class FullTest extends TestCase
{
    /** @covers App::run */
    public function test_generator_module(): void
    {
        define('USER', 'world');

        $module = function () {
            yield 'greeting' => value("hello %s\n");
            yield 'user' => constValue('USER');

            return factory(function ($greeting, $user) {
                printf($greeting, $user);
            }, ['greeting', 'user']);
        };

        $compiler = new Compiler();
        $compiler->addModule($module());

        $c = new Container($compiler->getFactories(), $compiler->getExtensions());
        $compiler->getCallback()($c);

        $this->expectOutputString("hello world\n");
    }

    /** @covers App::run */
    public function test_array_module(): void
    {
        define('USER', 'world');

        $module = [
            'greeting' => value("hello %s\n"),
            'user' => constValue('USER'),

            run(factory(function ($greeting, $user) {
                printf($greeting, $user);
            }, ['greeting', 'user'])),
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $c = new Container($compiler->getFactories(), $compiler->getExtensions());
        $compiler->getCallback()($c);

        $this->expectOutputString("hello world\n");
    }

    /** @covers Service::wire */
    public function test_wire(): void
    {
        $module = [
            'stack' => instance(SplStack::class),
            'add' => wire('stack', fn (SplStack $stack, $item) => $stack->push($item)),
            'item1' => value('JC')->wire('add'),
            'item2' => value('Denton')->wire('add'),
        ];

        $compiler = new Compiler([$module]);

        $c = new Container($compiler->getFactories(), $compiler->getExtensions());
        $compiler->getCallback()($c);

        $stack = $c->get('stack');

        $this->assertEquals(['JC', 'Denton'], iterator_to_array($stack));
    }
}
