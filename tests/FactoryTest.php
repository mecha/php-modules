<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\factory;

class FactoryTest extends TestCase
{
    /** @covers factory */
    public function test_no_deps(): void
    {
        $service = factory(fn () => 'foo');
        $cntr = new TestContainer();
        $actual = $service($cntr);

        $this->assertEquals('foo', $actual);
    }

    /** @covers factory */
    public function test_with_deps(): void
    {
        $service = factory(fn ($dep) => $dep + 3, ['dep']);
        $cntr = new TestContainer(['dep' => 5]);
        $actual = $service($cntr);

        $this->assertEquals(8, $actual);
    }
}
