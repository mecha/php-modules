<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\globalVar;

class GlobalVarTest extends TestCase
{
    /** @return array<string,mixed> */
    public static function provider(): array
    {
        global $test;
        $test = 'foo';

        return [
            'global var' => ['test', $test],
            'super global' => ['_SERVER', $_SERVER],
        ];
    }

    /**
     * @covers globalVar
     * @dataProvider provider
     * @param mixed $expected
     */
    public function test(string $name, $expected): void
    {
        global $test;
        $test = 'foo';

        $service = globalVar('test');
        $cntr = new TestContainer();

        $this->assertEquals('foo', $service($cntr));
    }
}
