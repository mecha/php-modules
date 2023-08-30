<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use DateTime;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\value;

class ValueTest extends TestCase
{
    /** @covers value */
    public function test_value(): void
    {
        $expected = new DateTime();
        $service = value($expected);
        $cntr = new TestContainer();

        $this->assertSame($expected, $service($cntr));
    }
}
