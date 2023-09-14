<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Container;
use Mecha\Modules\Psr7Compiler;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\run;
use function Mecha\Modules\value;

class RunTest extends TestCase
{
    /** @covers run */
    public function test_run(): void
    {
        $module = [
            run(
                function ($d1, $d2) {
                    echo "$d1 $d2\n";
                },
                ['d1', 'd2']
            ),
            'd1' => value('hello'),
            'd2' => value('world')
        ];

        $compiler = new Psr7Compiler();
        $compiler->addModule($module);
        $c = new Container($compiler->getFactories());

        $this->expectOutputString("hello world\n");

        foreach ($compiler->getActions() as $cb) {
            $cb($c);
        }
    }
}
