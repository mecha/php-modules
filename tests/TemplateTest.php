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
        $template = '%s is %.2f years old';
        $service = template($template, ['name', 'age']);

        $name = 'John';
        $age = 20;

        $c = new TestContainer(compact('name', 'age'));

        $actual = $service($c);
        $expected = sprintf($template, $name, $age);

        $this->assertSame($expected, $actual);
    }
}
