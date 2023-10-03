<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use PHPUnit\Framework\TestCase;
use Mecha\Modules\Stubs\TestContainer;

use function Mecha\Modules\mergeExtensions;

/** @covers MergedFactory */
class MergedFactoryTest extends TestCase
{
    public function test(): void
    {
        $c = new TestContainer();

        $factory = function ($arg) use ($c) {
            $this->assertSame($c, $arg, "Factory argument is not the container");
            return 'foobar';
        };

        $extension = function ($arg, $prev) use ($c) {
            $this->assertSame($c, $arg, "Extension first argument is not the container");
            $this->assertEquals('foobar', $prev, "Extension second argument is not the factory's return value");
            return 'lorem ipsum';
        };

        $merged = mergeExtensions(
            ['test' => $factory],
            ['test' => $extension]
        );

        $actual = $merged['test']($c);

        $this->assertEquals('lorem ipsum', $actual, "Merged factory's return value is not the extension's return value.");
    }
}
