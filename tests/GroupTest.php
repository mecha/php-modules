<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\group;

class GroupTest extends TestCase
{
    /** @covers group */
    public function test(): void
    {
        $module1 = [
            'foo' => fn () => 'foo',
            'bar' => fn () => 123,
        ];

        $module2 = [
            'baz' => fn () => new DateTime(),
        ];

        $group = group([$module1, $module2]);
        $services = [...$group];

        $this->assertSame($module1['foo'], $services['foo']);
        $this->assertSame($module1['bar'], $services['bar']);
        $this->assertSame($module2['baz'], $services['baz']);
    }
}
