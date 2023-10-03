<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\App;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\bind;
use function Mecha\Modules\value;

class AppTest extends TestCase
{
    /** @covers App */
    public function test(): void
    {
        $app = new App([
            [
                'foo' => value('foo'),
                'bar' => value('bar'),
            ],
            [
                'baz' => value('baz')
                    ->extends('bar', fn ($prev, $bar) => $bar)
                    ->runs(bind(function ($foo, $bar) {
                        echo $foo, $bar;
                    }, ['foo', 'bar'])),
            ]
        ]);

        $app->run();

        $this->expectOutputString('foobar');
    }
}
