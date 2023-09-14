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
        $callback = callback(fn ($a1, $a2, $d1, $d2) => "$a1-$a2-$d1-$d2", ['foo', 'bar']);
        $cntr = new TestContainer(['foo' => '56', 'bar' => '78']);
        $actual = $callback($cntr);

        $this->assertIsCallable($actual);
        $this->assertEquals('12-34-56-78', $actual('12', '34'));
    }
}
