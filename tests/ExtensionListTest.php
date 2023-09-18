<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\ExtensionList;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ExtensionListTest extends TestCase
{
    /** @covers ExtensionList::add, ExtensionList::__invoke */
    public function test(): void
    {
        $initial = 'foo';
        $middle = 'bar';
        $expected = 'baz';

        $extList = new ExtensionList();

        $extList->add(function($c, $prev) use ($initial, $middle) {
            $this->assertInstanceOf(ContainerInterface::class, $c);
            $this->assertEquals($initial, $prev);
            return $middle;
        });

        $extList->add(function($c, $prev) use ($middle, $expected) {
            $this->assertInstanceOf(ContainerInterface::class, $c);
            $this->assertEquals($middle, $prev);
            return $expected;
        });

        $actual = $extList(new TestContainer(), $initial);

        $this->assertEquals($expected, $actual);
    }
}
