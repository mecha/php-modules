<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\template;

class TemplateTest extends TestCase
{
    /** @covers template */
    public function test(): void
    {
        $c = new TestContainer([
            'name' => 'John',
            'age' => 20,
        ]);

        $service = template('%s is %.2f years old', ['name', 'age']);

        $actual = $service($c);
        $expect = 'John is 20.00 years old';

        $this->assertSame($expect, $actual);
    }
}
