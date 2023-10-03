<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Action;
use Mecha\Modules\Service;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use stdClass;
use Psr\Container\ContainerInterface;

/** @covers Service */
class ServiceTest extends TestCase
{
    /** @covers Service::__invoke */
    public function test_invoke(): void
    {
        $arg1 = 1;
        $arg2 = 2;

        $cntr = new TestContainer();
        $expected = new stdClass();

        $service = new Service(function ($c, $a1, $a2) use ($cntr, $arg1, $arg2, $expected) {
            $this->assertEquals($arg1, $a1);
            $this->assertEquals($arg2, $a2);
            return $expected;
        });

        $actual = $service($cntr, $arg1, $arg2);

        $this->assertSame($expected, $actual);
    }

    /** @covers Service::runs */
    public function test_run(): void
    {
        $s1 = new Service(fn () => null, ['foo', 'bar']);
        $s2 = $s1->runs($cb = fn () => printf('Running...'));

        $this->assertNotSame($s1, $s2);
        $this->assertCount(0, $s1->callbacks);
        $this->assertCount(1, $s2->callbacks);

        $c = new TestContainer();
        $s2->callbacks[0]($c);

        $this->expectOutputString('Running...');
    }

    /** @covers Service::runs */
    public function test_run_multiple(): void
    {
        $s1 = new Service(fn () => null, ['foo', 'bar']);
        $s2 = $s1->runs(fn () => printf("the sky "));
        $s3 = $s2->runs(fn () => printf("is falling"));

        $this->assertNotSame($s1, $s2);
        $this->assertCount(0, $s1->callbacks);
        $this->assertCount(1, $s2->callbacks);
        $this->assertCount(2, $s3->callbacks);

        $c = new TestContainer();
        $s3->callbacks[0]($c);
        $s3->callbacks[1]($c);

        $this->expectOutputString('the sky is falling');
    }

    /** @covers Service::extends */
    public function test_extends(): void
    {
        $cntr = new TestContainer();
        $expected = 10;

        $service1 = new Service(fn () => null);
        $service2 = $service1->extends('foo', function ($c, $prev) use ($cntr, $expected) {
            $this->assertSame($cntr, $c);
            $this->assertEquals(5, $prev);

            return $expected;
        });

        $this->assertNotSame($service1, $service2, 'Service should not be mutated after calling extends() on it.');
        $this->assertInstanceOf(Service::class, $service2, 'New service is not a Service instance.');
        $this->assertCount(1, $service2->extensions, 'New service does not have any extensions; expected one.');
        $this->assertArrayHasKey('foo', $service2->extensions, 'New service does not have an extension for "foo".');

        $actual = $service2->extensions['foo']($cntr, 5);

        $this->assertEquals($expected, $actual, 'Extension did not return expected value.');
    }

    /** @covers Service::wire */
    public function test_wire(): void
    {
        $s1 = new Service(fn () => 'foo');
        $s2 = $s1->wire('wire');

        $this->assertNotSame($s1, $s2, 'Service should not be mutatet after calling wire() on it.');
        $this->assertCount(0, $s1->actions, 'The original service should not have an action.');
        $this->assertCount(1, $s2->actions, 'The new service should have one action.');
        $this->assertArrayHasKey('wire', $s2->actions, 'The new service should have an action for "wire".');
    }

    /** @covers Service::calls */
    public function test_calls(): void
    {
        $s1 = new Service(fn () => 'foo');
        $s2 = $s1->calls('add');

        $this->assertNotSame($s1, $s2);
        $this->assertCount(0, $s1->extensions);
        $this->assertCount(1, $s2->extensions);
        $this->assertArrayHasKey('add', $s2->extensions);

        $c = new TestContainer();
        $value = $s2($c);

        $ext = $s2->extensions['add'];

        $list = [];
        $add = function ($item) use (&$list) {
            $list[] = $item;
        };

        $ret = $ext($c, $add, $value);

        $this->assertEquals(['foo'], $list, 'Action did not add the service value to the list.');
        $this->assertSame($add, $ret, 'Action extension should re-return the action.');
    }
}
