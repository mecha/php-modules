<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use ArrayObject;
use Mecha\Modules\Wire;
use PHPUnit\Framework\TestCase;

/** @covers Wire */
class WireTest extends TestCase
{
    public function test(): void
    {
        $subject = new ArrayObject();
        $inputs = ['foo', 'bar', 'baz'];

        $fn = function (ArrayObject $list, string $input) use ($subject) {
            $this->assertSame($subject, $list);
            $list[] = $input;
        };

        $wire = new Wire($fn, $inputs);
        $wire->trigger($subject);

        $this->assertSame($inputs, $subject->getArrayCopy());
    }
}
