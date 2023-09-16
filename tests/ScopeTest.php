<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Service;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\factory;
use function Mecha\Modules\prefixDeps;
use function Mecha\Modules\scope;
use function Mecha\Modules\scopeAssoc;
use function Mecha\Modules\value;

class ScopeTest extends TestCase
{
    /** @covers prefixDeps */
    public function test_prefixDeps(): void
    {
        $service = new Service(fn() => null, ['foo', 'bar', '@baz']);

        $actual = prefixDeps('prefix/', $service)->deps;
        $expected = ['prefix/foo', 'prefix/bar', 'baz'];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /** @covers prefixDeps */
    public function test_prefixDeps_action(): void
    {
        $service = new Service(fn() => null, ['foo']);
        $service = $service->then(fn() => null, ['bar', '@baz']);

        $actual = prefixDeps('prefix/', $service);

        $this->assertEquals(['prefix/foo'], $actual->deps);
        $this->assertCount(1, $actual->actions);
        $this->assertInstanceOf(Service::class, $actual->actions[0]);
        $this->assertEquals(['prefix/bar', 'baz'], $actual->actions[0]->deps);
    }

    /** @covers scope */
    public function test_scope_generator(): void
    {

        $foo = value(1);
        $bar = factory(fn () => null, ['foo']);
        $baz = factory(fn () => null, ['foo', 'bar', '@qux']);

        $module = function () use ($foo, $bar, $baz) {
            yield 'foo' => $foo;
            yield 'bar' => $bar;
            yield '@baz' => $baz;
        };

        $prefix = 'prefix/';
        $sModule = scope($prefix, $module());

        $actual = [...$sModule];
        $expected = [
            'prefix/foo' => prefixDeps($prefix, $foo),
            'prefix/bar' => prefixDeps($prefix, $bar),
            'baz' => prefixDeps($prefix, $baz),
        ];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /** @covers scope */
    public function test_scope_array(): void
    {
        $foo = value(1);
        $bar = factory(fn () => null, ['foo']);
        $baz = factory(fn () => null, ['foo', 'bar', '@qux']);

        $module = [
            'foo' => $foo,
            'bar' => $bar,
            'baz' => $baz,
        ];

        $prefix = 'prefix/';
        $sModule = scope($prefix, $module);

        $actual = [...$sModule];
        $expected = [
            'prefix/foo' => prefixDeps($prefix, $foo),
            'prefix/bar' => prefixDeps($prefix, $bar),
            'prefix/baz' => prefixDeps($prefix, $baz),
        ];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /** @covers scopeAssoc */
    public function test_scopeAssoc(): void
    {
        $modules = [
            'mod1' => [
                'foo' => new Service(fn () => null),
                'bar' => new Service(fn () => null),
            ],
            'mod2' => [
                'baz' => new Service(fn () => null),
                'qux' => new Service(fn () => null),
            ],
        ];

        $delim = '/';
        $actual = scopeAssoc($delim, $modules);
        $actual = [...$actual];

        $this->assertEquals(['mod1/foo', 'mod1/bar'], array_keys([...$actual[0]]));
        $this->assertEquals(['mod2/baz', 'mod2/qux'], array_keys([...$actual[1]]));
    }
}
