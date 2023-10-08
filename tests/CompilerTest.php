<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Container;
use Mecha\Modules\Compiler;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use function Mecha\Modules\action;
use function Mecha\Modules\bind;
use function Mecha\Modules\extend;
use function Mecha\Modules\factory;
use function Mecha\Modules\run;
use function Mecha\Modules\value;

/** @covers Compiler */
class CompilerTest extends TestCase
{
    public function test_compile_factories(): void
    {
        $module = [
            'value' => value('value'),
            'bound' => bind(fn($dep) => $dep, ['dep']),
            'service' => factory(fn($dep) => "{$dep}!!", ['dep']),
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $factories = $compiler->getFactories();

        $this->assertCount(3, $factories);
        $this->assertArrayHasKey('value', $factories);
        $this->assertArrayHasKey('bound', $factories);
        $this->assertArrayHasKey('service', $factories);

        $c = new TestContainer(['dep' => 'foobar']);

        $this->assertEquals('value', $factories['value']($c));
        $this->assertEquals('foobar', $factories['bound']($c));
        $this->assertEquals('foobar!!', $factories['service']($c));
    }

    public function test_compile_extensions(): void
    {
        $module = [
            'attached1' => value('value')->extends('foo', fn($c, $prev) => "!!$prev"),
            'attached2' => value('value')->extends('foo', fn($c, $prev) => "$prev!!"),

            'anonymous1' => extend('bar', fn($c, $prev) => "!!$prev"),
            'anonymous2' => extend('bar', fn($c, $prev) => "$prev!!"),
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $extensions = $compiler->getExtensions();

        $this->assertCount(2, $extensions);
        $this->assertArrayHasKey('foo', $extensions);
        $this->assertArrayHasKey('bar', $extensions);

        $c = new TestContainer();

        $this->assertEquals('!!FOO!!', $extensions['foo']($c, 'FOO'));
        $this->assertEquals('!!BAR!!', $extensions['bar']($c, 'BAR'));
    }

    public function test_compile_actions(): void
    {
        $module = [
            'value' => value('foobar'),

            'ext1' => extend('value', fn($c, $prev) => "!!$prev"),
            'ext2' => extend('value', fn($c, $prev) => "$prev!!"),

            'anon_action' => action('value', fn($c, $prev) => printf($prev . "\n")),
            'attached_action' => value('dummy')->on('value', fn($c, $prev) => printf($prev . "\n")),
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $extensions = $compiler->getExtensions();

        $this->assertCount(1, $extensions);
        $this->assertArrayHasKey('value', $extensions);

        $c = new TestContainer();

        $this->assertEquals('!!foobar!!', $extensions['value']($c, 'foobar'));
        $this->expectOutputString("!!foobar!!\n!!foobar!!\n");
    }

    public function test_compile_callbacks(): void
    {
        $module = [
            'attached' => value('value')->runs(fn($c) => printf('foo')),
            'anonymous' => run(fn($c) => printf('bar')),
        ];

        $compiler = new Compiler();
        $compiler->addModule($module);

        $callback = $compiler->getCallback();
        $callback($c);

        $this->expectOutputString('foobar');
    } 

    public function test_run_callback(): void
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
