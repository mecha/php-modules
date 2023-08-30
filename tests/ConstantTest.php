<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\constant;

class ConstantTest extends TestCase
{
    /** @covers constant */
    public function test_constant(): void
    {
        define('MY_CONSTANT', 'my value');

        $service = constant('MY_CONSTANT');
        $cntr = new TestContainer();
        $actual = $service($cntr);

        $this->assertEquals('my value', $actual);
    }
}
