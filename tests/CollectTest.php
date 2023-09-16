<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\collect;

class CollectTest extends TestCase
{
    /** @covers collect */
    public function test(): void
    {
        $service = collect(['foo', 'bar', 'baz']);
        $cntr = new TestContainer(['foo' => 123, 'bar' => 456, 'baz' => 789]);
        $actual = $service($cntr);

        $this->assertSame([123, 456, 789], $actual);
    }
}
