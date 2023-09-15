<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\extend;

class ExtendTest extends TestCase
{
    /** @covers extend */
    public function test(): void
    {
        $service = extend(function ($list, $item) {
            $list[] = $item;
            return $list;
        }, ['item']);

        $cntr = new TestContainer([
            'item' => 'foo',
        ]);

        $actual = $service($cntr, ['hello', 'world']);

        $this->assertEquals(['hello', 'world', 'foo'], $actual);
    }
}
