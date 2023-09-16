<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\constValue;

const CONST_VAL = 'a const value';
define('DEFINED_VALUE', 'a defined value');

class ConstValueTest extends TestCase
{
    /** @return array<string,array<int,mixed>> */
    public static function provider(): array
    {
        return [
            'const' => [__NAMESPACE__ . '\\CONST_VAL', CONST_VAL],
            'defined' => ['DEFINED_VALUE', DEFINED_VALUE],
        ];
    }

    /**
     * @covers constant
     * @dataProvider provider
     */
    public function test(string $name, string $expected): void
    {
        $service = constValue($name);
        $cntr = new TestContainer();
        $actual = $service($cntr);

        $this->assertEquals($expected, $actual);
    }
}
