<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use ArrayObject;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\instance;
use function Mecha\Modules\value;

class InstanceTest extends TestCase
{
    /** @covers instance */
    public function test_no_deps(): void
    {
        $actual = instance(ArrayObject::class);
        $c = new TestContainer();

        $this->assertInstanceOf(ArrayObject::class, $actual($c));
    }

    /** @covers instance */
    public function test_with_deps(): void
    {
        $actual = instance(ArrayObject::class, ["items"]);
        $c = new TestContainer(["items" => [1, 3, 5, 7]]);

        $this->assertEquals(new ArrayObject([1, 3, 5, 7]), $actual($c));
    }

    /** @covers instance */
    public function test_with_inline_deps(): void
    {
        $actual = instance(ArrayObject::class, [value([2, 4, 6, 8])]);
        $c = new TestContainer();

        $this->assertEquals(new ArrayObject([2, 4, 6, 8]), $actual($c));
    }
}
