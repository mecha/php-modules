<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use PHPUnit\Framework\TestCase;
use function Mecha\Modules\factory;
use function Mecha\Modules\scope;
use function Mecha\Modules\value;

class ScopeTest extends TestCase
{
    /** @covers scope */
    public function test_scope(): void {

        $foo = value(1);
        $bar = factory(fn() => null, ['foo']);
        $baz = factory(fn() => null, ['foo', 'bar', '@qux']);

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
}
