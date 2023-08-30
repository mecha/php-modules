<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use function Mecha\Modules\globalVar;

class GlobalVarTest extends TestCase
{
    /** @covers globalVar */
    public function test_globalVar(): void
    {
        global $test;
        $test = 'foo';

        $service = globalVar('test');
        $cntr = new TestContainer();

        $this->assertEquals('foo', $service($cntr));
    }
}
