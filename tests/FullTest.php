<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\App;
use PHPUnit\Framework\TestCase;

use function Mecha\Modules\scope;
use function Mecha\Modules\factory;
use function Mecha\Modules\run;
use function Mecha\Modules\value;
use function Mecha\Modules\constValue;

class FullTest extends TestCase
{
    /** @covers App::run */
    public function test_generator_module(): void
    {
        define('USER', 'world');

        $module = function () {
            yield 'greeting' => value("hello %s\n");
            yield 'user' => constValue('USER');

            return factory(function ($greeting, $user) {
                printf($greeting, $user);
            }, ['greeting', 'user']);
        };

        $sModule = scope('foo/', $module());

        $app = new App();
        $app->addModule($sModule);

        ob_start();
        $app->run();
        $actual = ob_get_clean();

        $expected = "hello world\n";

        $this->assertEquals($expected, $actual);
    }

    /** @covers App::run */
    public function test_array_module(): void
    {
        define('USER', 'world');

        $module = function () {
            yield 'greeting' => value("hello %s\n");
            yield 'user' => constValue('USER');

            yield run(function ($greeting, $user) {
                printf($greeting, $user);
            }, ['greeting', 'user']);
        };

        $sModule = scope('foo/', $module());

        $app = new App();
        $app->addModule($sModule);

        ob_start();
        $app->run();
        $actual = ob_get_clean();

        $expected = "hello world\n";

        $this->assertEquals($expected, $actual);
    }
}
