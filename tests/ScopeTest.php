<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Service;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\bind;
use function Mecha\Modules\factory;
use function Mecha\Modules\getDeps;
use function Mecha\Modules\prefixDeps;
use function Mecha\Modules\scope;
use function Mecha\Modules\scopeAssoc;
use function Mecha\Modules\value;

class ScopeTest extends TestCase
{
    /** @covers prefixDeps */
    public function test_prefixDeps_raw(): void
    {
        $service = fn () => null;
        $actual = prefixDeps('prefix/', $service);

        $this->assertIsCallable($actual, 'Prefixed raw service is not callable');
    }

    /** @covers prefixDeps */
    public function test_prefixDeps_service(): void
    {
        $service = new Service(fn() => null);
        $actual = prefixDeps('prefix/', $service);

        $this->assertEquals($service, $actual);
    }

    /** @covers prefixDeps */
    public function test_prefixDeps_extensions(): void
    {
        $service = new Service(fn() => null);
        $service = $service->extends('foo', bind(fn($foo, $bar, $baz) => null, ['@bar', 'baz']));

        $actual = prefixDeps('prefix/', $service);

        $this->assertCount(1, $actual->extensions);
        $this->assertArrayHasKey('prefix/foo', $actual->extensions);
        $this->assertIsCallable($actual->extensions['prefix/foo']);
        $this->assertEquals(['bar', 'prefix/baz'], getDeps($actual->extensions['prefix/foo']));
    }

    /** @covers prefixDeps */
    public function test_prefixDeps_actions(): void
    {
        $service = new Service(fn() => null);
        $service = $service->on('foo', bind(fn($foo, $bar, $baz) => null, ['@bar', 'baz']));

        $actual = prefixDeps('prefix/', $service);

        $this->assertCount(1, $actual->actions);
        $this->assertArrayHasKey('prefix/foo', $actual->actions);
        $this->assertIsCallable($actual->actions['prefix/foo']);
        $this->assertEquals(['bar', 'prefix/baz'], getDeps($actual->actions['prefix/foo'])); 
    }

    /** @covers prefixDeps */
    public function test_prefixDeps_callbacks(): void
    {
        $service = new Service(fn() => null);
        $service = $service->runs(bind(fn($foo, $bar) => null, ['foo', '@bar']));

        $actual = prefixDeps('prefix/', $service);

        $this->assertCount(1, $actual->callbacks);
        $this->assertIsCallable($actual->callbacks[0]);
        $this->assertEquals(['prefix/foo', 'bar'], getDeps($actual->callbacks[0]));
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
            'prefix/foo' => value(1),
            'prefix/bar' => factory(fn() => null, ['prefix/foo']),
            'baz' => factory(fn() => null, ['prefix/foo', 'prefix/bar', 'qux']),
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
            '@baz' => $baz,
        ];

        $prefix = 'prefix/';
        $sModule = scope($prefix, $module);

        $actual = [...$sModule];
        $expected = [
            'prefix/foo' => value(1),
            'prefix/bar' => factory(fn() => null, ['prefix/foo']),
            'baz' => factory(fn() => null, ['prefix/foo', 'prefix/bar', 'qux']),
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
