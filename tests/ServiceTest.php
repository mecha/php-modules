<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use ArrayObject;
use Mecha\Modules\Container;
use Mecha\Modules\Psr7Compiler;
use Mecha\Modules\Service;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use stdClass;
use function Mecha\Modules\callback;

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

    /** @covers Service::prefixDeps */
    public function test_prefixDeps(): void
    {
        $service = new Service(fn() => null, ['foo', 'bar', '@baz']);

        $actual = $service->prefixDeps('prefix/')->deps;
        $expected = ['prefix/foo', 'prefix/bar', 'baz'];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /** @covers Service::prefixDeps */
    public function test_prefixDeps_action(): void
    {
        $service = new Service(fn() => null, ['foo']);
        $service = $service->then(fn() => null, ['bar', '@baz']);

        $actual = $service->prefixDeps('prefix/');

        $this->assertEquals(['prefix/foo'], $actual->deps);
        $this->assertCount(1, $actual->actions);
        $this->assertInstanceOf(Service::class, $actual->actions[0]);
        $this->assertEquals(['prefix/bar', 'baz'], $actual->actions[0]->deps);
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
}
