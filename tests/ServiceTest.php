<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use ArrayObject;
use Mecha\Modules\Container;
use Mecha\Modules\Psr7Compiler;
use Mecha\Modules\Service;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use SplStack;
use stdClass;
use function Mecha\Modules\callback;
use function Mecha\Modules\factory;
use function Mecha\Modules\value;

class ServiceTest extends TestCase
{
    /** @covers Service::__invoke */
    public function test_invoke(): void
    {
        $cntr = new TestContainer(['foo' => 1, 'bar' => 2]);

        $expected = new stdClass();
        $service = new Service(function ($deps) use ($expected) {
            $this->assertEqualsCanonicalizing([1, 2], $deps);
            return $expected;
        }, ['foo', 'bar']);

        $actual = $service($cntr);

        $this->assertSame($expected, $actual);
    }

    /** @covers Service::then */
    public function test_then(): void
    {
        $s1 = new Service(fn() => null, ['foo', 'bar']);
        $s2 = $s1->then($cb = fn() => null);

        $this->assertNotSame($s1, $s2);
        $this->assertCount(0, $s1->actions);
        $this->assertCount(1, $s2->actions);
    }

    /** @covers Service::then */
    public function test_then_multiple(): void
    {
        $s1 = new Service(fn() => null, ['foo', 'bar']);
        $s2 = $s1->then(fn() => null);
        $s3 = $s2->then(fn() => null);

        $this->assertNotSame($s1, $s2);
        $this->assertCount(0, $s1->actions);
        $this->assertCount(1, $s2->actions);
        $this->assertCount(2, $s3->actions);
    }

    /** @covers Service::thenUse */
    public function test_thenUse(): void
    {
        $s1 = new Service(fn() => 15);
        $s2 = $s1->thenUse('action');

        $this->assertNotSame($s1, $s2);
        $this->assertCount(0, $s1->actions);
        $this->assertCount(1, $s2->actions);

        $compiler = new Psr7Compiler();
        $compiler->addModule([
            'item' => $s2,
            'list' => fn () => new ArrayObject([6, 9]),
            'action' => callback(fn($value, $list) => $list[] = $value, ['list']),
        ]);

        $c = new Container($compiler->getFactories());
        
        foreach ($compiler->getActions() as $action) {
            $action($c);
        }

        $list = $c->get('list');

        $this->assertEqualsCanonicalizing([6, 9, 15], $list->getArrayCopy());
    }

    /** @covers Service::extends */
    public function test_extends(): void
    {
        $initial = 'foo';
        $expected = 'baz';

        $ext = new Service(fn () => $expected);
        $ext = $ext->extends('foo', function ($prev, $self) use ($initial, $expected) {
            $this->assertEquals($initial, $prev);
            $this->assertEquals($expected, $self);

            return $self;
        });

        $this->assertCount(1, $ext->extensions);

        $compiler = new Psr7Compiler();
        $compiler->addModule([
            'foo' => fn() => $initial,
            'bar' => $ext,
        ]);

        $factories = $compiler->getFactories();
        $extensions = $compiler->getExtensions();

        $this->assertCount(1, $extensions);

        $c = new Container($factories, $extensions);
        $actual = $c->get('foo');

        $this->assertEquals('baz', $actual);
    }

    /** @covers Service::use */
    public function test_use(): void
    {
        $compiler = new Psr7Compiler();
        $compiler->addModule([
            'stack' => factory(fn () => new SplStack())->then(fn() => null, ['add']),
            'add' => callback(fn($value, $list) => $list->push($value), ['stack']),
            'foo' => value('foo')->use('add'),
        ]);

        $factories = $compiler->getFactories();
        $extensions = $compiler->getExtensions();
        $action = $compiler->getMergedAction();

        $this->assertCount(1, $extensions);
        $this->assertArrayHasKey('add', $extensions);

        $c = new Container($factories, $extensions);
        $action($c);

        $actual = $c->get('stack');

        $this->assertEquals(['foo'], [...$actual]);
    }
}
