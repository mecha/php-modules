<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\callback;

class CallbackTest extends TestCase
{
    /** @covers callback */
    public function test_with_args(): void
    {
        $callback = callback(fn ($a1, $a2) => "$a1-$a2");
        $cntr = new TestContainer();
        $actual = $callback($cntr);

        $this->assertIsCallable($actual);
        $this->assertEquals('12-34', $actual('12', '34'));
    }

    /** @covers callback */
    public function test_with_deps(): void
    {
        $callback = callback(fn ($d1, $d2) => "$d1-$d2", ['foo', 'bar']);
        $cntr = new TestContainer(['foo' => '12', 'bar' => '34']);
        $actual = $callback($cntr);

        $this->assertIsCallable($actual);
        $this->assertEquals('12-34', $actual());
    }

    /** @covers callback */
    public function test_with_args_and_deps(): void
    {
        $callback = callback(fn ($a1, $a2, $d1, $d2) => "$a1-$a2-$d1-$d2", ['foo', 'bar']);
        $cntr = new TestContainer(['foo' => '56', 'bar' => '78']);
        $actual = $callback($cntr);

        $this->assertIsCallable($actual);
        $this->assertEquals('12-34-56-78', $actual('12', '34'));
    }
}
