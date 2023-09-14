<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\env;

class EnvTest extends TestCase
{
    /** @covers env */
    public function test(): void
    {
        $_ENV['foo'] = 'bar';

        $env = env('foo');
        $cntr = new TestContainer();
        $actual = $env($cntr);

        $this->assertEquals('bar', $actual);
    }
}
