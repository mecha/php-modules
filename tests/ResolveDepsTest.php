<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use ArrayObject;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use function Mecha\Modules\instance;
use function Mecha\Modules\resolveDeps;
use function Mecha\Modules\value;

class ResolveDepsTest extends TestCase
{
    /** @covers resolveDeps */
    public function test_with_ids(): void
    {
        $cntr = new TestContainer(['foo' => 1, 'bar' => 2]);
        $actual = resolveDeps($cntr, ['foo', 'bar']);

        $this->assertEqualsCanonicalizing([1, 2], $actual);
    }

    /** @covers resolveDeps */
    public function test_with_services(): void
    {
        $cntr = new TestContainer();
        $actual = resolveDeps($cntr, [
            value('foo'),
            instance(ArrayObject::class),
        ]);

        $this->assertEqualsCanonicalizing(['foo', new ArrayObject()], $actual);
    }
}
