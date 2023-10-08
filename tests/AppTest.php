<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use LogicException;
use Mecha\Modules\App;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\bind;
use function Mecha\Modules\value;

/** @covers App */
class AppTest extends TestCase
{
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

    public function test_get(): void
    {
        $app = new App([
            [
                'foo' => fn () => 'foo',
            ]
        ]);

        $app->run();

        $this->assertSame('foo', $app->get('foo'));
    }

    public function test_get_before_run(): void
    {
        $app = new App([
            [
                'foo' => fn () => 'foo',
            ]
        ]);

        $this->expectException(LogicException::class);

        $app->get('foo');
    }
}
