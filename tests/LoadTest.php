<?php

declare(strict_types=1);

namespace Mecha\Modules\Test;

use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\load;

class LoadTest extends TestCase
{
    /** @covers load */
    public function test(): void
    {
        $c = new TestContainer(['a' => 54, 'b' => 15]);
        $service = load(__DIR__ . '/files/sum.php', ['a', 'b']);
        $actual = $service($c);

        $this->assertSame(54 + 15, $actual);
    }
}
