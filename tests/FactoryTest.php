<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\factory;

class FactoryTest extends TestCase
{
    /** @covers factory */
    public function test_factory(): void
    {
        $service = factory(fn ($a) => $a + 3, ['a']);
        $cntr = new TestContainer(['a' => 5]);
        $actual = $service($cntr);

        $this->assertEquals(8, $actual);
    }
}
