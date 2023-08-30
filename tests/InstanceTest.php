<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use ArrayObject;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\instance;

class InstanceTest extends TestCase
{
    /** @covers instance */
    public function test_instance(): void
    {
        $actual = instance(ArrayObject::class, ["items"]);
        $cntr = new TestContainer(["items" => [1, 3, 5, 7]]);

        $this->assertEquals(new ArrayObject([1, 3, 5, 7]), $actual($cntr));
    }
}
