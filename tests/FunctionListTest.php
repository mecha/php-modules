<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\FunctionList;
use PHPUnit\Framework\TestCase;

class FunctionListTest extends TestCase
{
    /** @param list<mixed> $expectedArgs */
    protected function createTestFn(string $output, array $expectedArgs): callable
    {
        return function () use ($expectedArgs, $output) {
            $this->assertEquals($expectedArgs, func_get_args(), 'Function did not receive the correct args.');
            echo $output;
        };
    }

    /** @covers FunctionList:__construct, FunctionList::__invoke */
    public function test(): void
    {
        $expectedArgs = [1, 2, 3];

        $list = new FunctionList([
            $this->createTestFn("it's the ", $expectedArgs),
            $this->createTestFn("final ", $expectedArgs),
            $this->createTestFn("countdown", $expectedArgs),
        ]);

        $list(...$expectedArgs);

        $this->expectOutputString("it's the final countdown");
    }

    /** @covers FunctionList::add */
    public function test_add(): void
    {
        $expectedArgs = [1, 2, 3];

        $list = new FunctionList([
            $this->createTestFn("it's the ", $expectedArgs),
        ]);

        $list->add($this->createTestFn("final ", $expectedArgs));
        $list->add($this->createTestFn("countdown", $expectedArgs));

        $list(...$expectedArgs);

        $this->expectOutputString("it's the final countdown");
    }
}
