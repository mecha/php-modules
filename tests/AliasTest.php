<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\alias;

class AliasTest extends TestCase
{
    /** @covers alias */
    public function test(): void
    {
        $alias = alias('foo');
        $cntr = new TestContainer(['foo' => 'bar']);
        $actual = $alias($cntr);

        $this->assertEquals('bar', $actual);
    }

    /** @covers alias */
    public function test_not_exists(): void
    {
        $alias = alias('baz');
        $cntr = new TestContainer(['foo' => 'bar']);
        $actual = $alias($cntr);

        $this->assertNull($actual);
    }
}
