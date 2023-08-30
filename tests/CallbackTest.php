<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\callback;

class CallbackTest extends TestCase
{
    /** @covers callback */
    public function test_callback(): void
    {
        $alias = callback(fn ($d1, $d2, $a1) => "$d1-$d2-$a1", ['foo', 'bar']);
        $cntr = new TestContainer(['foo' => '123', 'bar' => '456']);
        $actual = $alias($cntr);

        $this->assertIsCallable($actual);
        $this->assertEquals('123-456-789', $actual('789'));
    }
}
