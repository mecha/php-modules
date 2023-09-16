<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Mecha\Modules\value;

class ValueTest extends TestCase
{
    /** @return array<string,mixed> */
    public static function provider(): array
    {
        return [
            'int' => [1],
            'float' => [1.1],
            'string' => ['foo'],
            'bool' => [true],
            'array' => [[1, 2, 3]],
            'obj' => [new stdClass()],
            'null' => [null],
            'fn' => [fn () => 5],
        ];
    }

    /**
     * @covers value
     * @dataProvider provider
     * @param mixed $expected
     */
    public function test($expected): void
    {
        $c = new TestContainer();

        $service = value($expected);
        $actual = $service($c);

        $this->assertSame($expected, $actual);
    }
}
