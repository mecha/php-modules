<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Service;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\factory;
use function Mecha\Modules\scope;
use function Mecha\Modules\scopeAssoc;
use function Mecha\Modules\value;

class ScopeTest extends TestCase
{
    /** @covers scope */
    public function test_scope_generator(): void
    {

        $foo = value(1);
        $bar = factory(fn () => null, ['foo']);
        $baz = factory(fn () => null, ['foo', 'bar', '@qux']);

        $module = function () use ($foo, $bar, $baz) {
            yield 'foo' => $foo;
            yield 'bar' => $bar;
            yield 'baz' => $baz;
        };

        $prefix = 'prefix/';
        $sModule = scope($prefix, $module());

        $actual = [...$sModule];
        $expected = [
            'prefix/foo' => $foo->prefixDeps($prefix),
            'prefix/bar' => $bar->prefixDeps($prefix),
            'prefix/baz' => $baz->prefixDeps($prefix),
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
            'prefix/foo' => $foo->prefixDeps($prefix),
            'prefix/bar' => $bar->prefixDeps($prefix),
            'prefix/baz' => $baz->prefixDeps($prefix),
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
