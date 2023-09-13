<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\App;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\run;
use function Mecha\Modules\value;

class RunTest extends TestCase
{
    /** @covers run */
    public function test_run(): void
    {
        $module = [
            'test' => run(
                function ($d1, $d2) {
                    echo "$d1 $d2\n";
                },
                ['d1', 'd2']
            ),
            'd1' => value('hello'),
            'd2' => value('world')
        ];

        $app = new App();
        $app->addModule($module);

        $this->expectOutputString("hello world\n");
        $app->run();
    }
}
