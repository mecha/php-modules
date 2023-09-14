<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\constValue;

const OUTSIDE_CONST = 'a const for testing';

class ConstValueTest extends TestCase
{
    /** @covers constant */
    public function test_define(): void
    {
        define('MY_CONSTANT', 'my value');

        $service = constValue('MY_CONSTANT');
        $cntr = new TestContainer();
        $actual = $service($cntr);

        $this->assertEquals('my value', $actual);
    }

    /** @covers constant */
    public function test_const(): void
    {
        $service = constValue(__NAMESPACE__ . '\\OUTSIDE_CONST');
        $cntr = new TestContainer();
        $actual = $service($cntr);

        $this->assertEquals(OUTSIDE_CONST, $actual);
    }
}
