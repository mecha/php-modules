<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use ArrayObject;
use Mecha\Modules\Service;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use SplStack;
use function Mecha\Modules\alias;
use function Mecha\Modules\bind;
use function Mecha\Modules\callback;
use function Mecha\Modules\collect;
use function Mecha\Modules\constValue;
use function Mecha\Modules\extend;
use function Mecha\Modules\env;
use function Mecha\Modules\factory;
use function Mecha\Modules\globalVar;
use function Mecha\Modules\instance;
use function Mecha\Modules\invoke;
use function Mecha\Modules\load;
use function Mecha\Modules\run;
use function Mecha\Modules\service;
use function Mecha\Modules\template;
use function Mecha\Modules\value;

define('MY_DEFINED', 12345);
const MY_CONST = 12345;

class ServiceHelpersTest extends TestCase
{
    /** @covers bind */
    public function test_bind(): void
    {
        $c = new TestContainer(['a' => 1, 'b' => 2]);

        $bound = bind(fn ($a, $b) => $a + $b, ['a', 'b']);
        $actual = $bound($c);

        $this->assertEquals(3, $actual, 'Bound function did not resolve to the expected value.');
    }

    /** @covers bind */
    public function test_bind_no_deps(): void
    {
        $c = new TestContainer();

        $bound = bind(fn () => func_get_args());
        $actual = $bound($c);

        $this->assertEquals([], $actual, 'Bound function did not resolve to the expected value.');
    }

    /** @covers bind */
    public function test_bind_args(): void
    {
        $c = new TestContainer(['dep1' => 3, 'dep2' => 4]);

        $bound = bind(fn ($a1, $a2, $d1, $d2) => $a1 . $a2 . $d1 . $d2, ['dep1', 'dep2']);
        $actual = $bound($c, 1, 2);

        $this->assertEquals('1234', $actual, 'Bound function did not resolve to the expected value.');
    }

    /** @covers service */
    public function test_service(): void
    {
        $c = new TestContainer(['foo' => 123]);

        $service = service(fn (ContainerInterface $c) => $c->get('foo'));
        $actual = $service($c);

        $this->assertEquals(123, $actual, 'Service did not resolve to the expected value.');
        $this->assertInstanceOf(Service::class, $service, 'Wrapped defintion is not an instance of `Service`.');
    }

    /** @covers value */
    public function test_value(): void
    {
        $c = new TestContainer();

        $value = value('hello');
        $actual = $value($c);

        $this->assertEquals('hello', $actual, 'Value did not resolve to the expected value.');
    }

    /** @covers globalVar */
    public function test_globalVar(): void
    {
        global $foo;
        $foo = 12345;

        $c = new TestContainer();

        $glob = globalVar('foo');
        $actual = $glob($c);

        $this->assertEquals($foo, $actual, 'Global variable did not resolve to the expected value.');
    }

    /** @covers constValue */
    public function test_constValue_define(): void
    {
        $c = new TestContainer();

        $const = constValue('MY_DEFINED');
        $actual = $const($c);

        $this->assertEquals(MY_DEFINED, $actual, 'Const value did not resolve to the expected value.');
    }

    /** @covers constValue */
    public function test_constValue_const(): void
    {
        $c = new TestContainer();

        $const = constValue(__NAMESPACE__ . '\MY_CONST');
        $actual = $const($c);

        $this->assertEquals(MY_CONST, $actual, 'Const value did not resolve to the expected value.');
    }

    /** @covers env */
    public function test_env(): void
    {
        $_ENV['FOO'] = 12345;

        $c = new TestContainer();

        $env = env('FOO');
        $actual = $env($c);

        $this->assertEquals(12345, $actual, 'Env service did not resolve to the expected value.');
    }

    /** @covers alias */
    public function test_alias(): void
    {
        $c = new TestContainer(['foo' => 12345]);

        $alias = alias('foo');
        $actual = $alias($c);

        $this->assertEquals(12345, $actual, 'Alias service did not resolve to the expected value.');
    }

    /** @covers template */
    public function test_template(): void
    {
        $c = new TestContainer([
            'name' => 'JC Denton',
            'age' => 12,
        ]);

        $template = template('%s had %d rounds left in his magazine.', ['name', 'age']);
        $actual = $template($c);

        $this->assertEquals('JC Denton had 12 rounds left in his magazine.', $actual, 'Template did not resolve to the expected value.');
    }

    /** @covers instance */
    public function test_instance(): void
    {
        $c = new TestContainer([
            'items' => [1, 2, 3]
        ]);

        $instance = instance(ArrayObject::class, ['items']);
        $actual = $instance($c);

        $this->assertEquals(new ArrayObject([1, 2, 3]), $actual, 'Instance service did not resolve to the expected value.');
    }

    /** @covers instance */
    public function test_instance_no_deps(): void
    {
        $c = new TestContainer();

        $instance = instance(ArrayObject::class);
        $actual = $instance($c);

        $this->assertEquals(new ArrayObject(), $actual, 'Instance service did not resolve to the expected value.');
    }

    /** @covers factory */
    public function test_factory(): void
    {
        $c = new TestContainer([
            'foo' => 6,
            'bar' => 9,
        ]);

        $factory = factory(fn ($d1, $d2) => [$d1, $d2], ['foo', 'bar']);
        $actual = $factory($c);

        $this->assertEquals([6, 9], $actual, 'Factory service did not resolve to the expected value.');
    }

    /** @covers callback */
    public function test_callback(): void
    {
        $c = new TestContainer([
            'dep1' => 3,
            'dep2' => 4,
        ]);

        $service = callback(fn ($a1, $a2, $d1, $d2) => $a1 . $a2 . $d1 . $d2, ['dep1', 'dep2']);
        $callback = $service($c);

        $this->assertIsCallable($callback, 'Callback did not resolve to a callable value.');

        $return = $callback(1, 2);
        $this->assertEquals('1234', $return, 'Callback did not return the expected value.');
    }

    /** @covers callback */
    public function test_callback_no_args(): void
    {
        $c = new TestContainer([
            'dep1' => 3,
            'dep2' => 4,
        ]);

        $service = callback(fn ($d1, $d2) => $d1 . $d2, ['dep1', 'dep2']);
        $callback = $service($c);

        $this->assertIsCallable($callback, 'Callback did not resolve to a callable value.');

        $return = $callback();
        $this->assertEquals('34', $return, 'Callback did not return the expected value.');
    }

    /** @covers callback */
    public function test_callback_no_deps(): void
    {
        $c = new TestContainer();

        $service = callback(fn ($a1, $a2) => $a1 . $a2);
        $callback = $service($c);

        $this->assertIsCallable($callback, 'Callback did not resolve to a callable value.');

        $return = $callback(1, 2);
        $this->assertEquals('12', $return, 'Callback did not return the expected value.');
    }

    /** @covers collect */
    public function test_collect(): void
    {
        $c = new TestContainer([
            'foo' => 1,
            'bar' => 2,
        ]);

        $collect = collect(['foo', 'bar']);
        $actual = $collect($c);

        $this->assertEquals([1, 2], $actual, 'Collect service did not resolve to the expected value.');
    }

    /** @covers load */
    public function test_load(): void
    {
        $c = new TestContainer([
            'foo' => 1,
            'bar' => 2,
        ]);

        $load = load(__DIR__ . '/files/sum.php', ['foo', 'bar']);
        $actual = $load($c);

        $this->assertEquals(3, $actual, 'Load service did not resolve to the expected value.');
    }


    /** @covers load */
    public function test_load_no_deps(): void
    {
        $c = new TestContainer([
            'foo' => 1,
            'bar' => 2,
        ]);

        $load = load(__DIR__ . '/files/service.php');
        $actual = $load($c);

        $expected = (require __DIR__ . '/files/service.php')($c);

        $this->assertEquals($expected, $actual, 'Load service did not resolve to the expected value.');
    }

    /** @covers invoke */
    public function test_invoke(): void
    {
        $c = new TestContainer([
            'action' => fn ($a1, $a2) => "[$a1, $a2]",
            'foo' => 'foo',
            'bar' => 'bar',
        ]);

        $callback = invoke('action', ['foo', 'bar']);
        $actual = $callback($c);

        $this->assertEquals('[foo, bar]', $actual, 'Callback service did not resolve to the expected value.');
    }

    /** @covers invoke */
    public function test_invoke_no_deps(): void
    {
        $c = new TestContainer([
            'action' => fn () => "hello",
        ]);

        $callback = invoke('action');
        $actual = $callback($c);

        $this->assertEquals('hello', $actual, 'Callback service did not resolve to the expected value.');
    }

    /** @covers run */
    public function test_run(): void
    {
        $c = new TestContainer([
            'name' => 'Thomas Anderson',
        ]);

        $service = run(fn ($c) => printf('Hello %s', $c->get('name')));

        $this->assertInstanceOf(Service::class, $service, 'The run() helper did not return a Service instance.');
        $this->assertCount(1, $service->callbacks);

        $service->callbacks[0]($c);
        $this->expectOutputString('Hello Thomas Anderson');
    }

    /** @covers run, bind */
    public function test_run_bind(): void
    {
        $c = new TestContainer([
            'name' => 'Thomas Anderson',
        ]);

        $service = run(bind(fn ($name) => printf('Hello %s', $name), ['name']));

        $this->assertInstanceOf(Service::class, $service, 'The run() helper did not return a Service instance.');
        $this->assertCount(1, $service->callbacks, 'The run() helper did not attach an action to the dummy service.');

        $service->callbacks[0]($c);
        $this->expectOutputString('Hello Thomas Anderson');
    }

    /** @covers extend */
    public function test_extend(): void
    {
        $c = new TestContainer([
            'list' => new SplStack(), 
            'item' => 'foobar',
        ]);

        $ext = extend('list', fn($c, $list) => $list->push($c->get('item')));

        $this->assertInstanceOf(Service::class, $ext, 'The ext() helper did not return a Service instance.');
        $this->assertCount(1, $ext->extensions, 'The ext() helper did not attach an extension to the dummy service.');

        $list = $c->get('list');
        $ext->extensions['list']($c, $list);

        $this->assertEquals(['foobar'], [...$list]);
    }

    /** @covers extend */
    public function test_extend_bind(): void
    {
        $c = new TestContainer([
            'list' => new SplStack(), 
            'item' => 'foobar',
        ]);

        $ext = extend('list', bind(fn($list, $item) => $list->push($item), ['item']));

        $this->assertInstanceOf(Service::class, $ext, 'The ext() helper did not return a Service instance.');
        $this->assertCount(1, $ext->extensions, 'The ext() helper did not attach an extension to the dummy service.');

        $list = $c->get('list');
        $ext->extensions['list']($c, $list);

        $this->assertEquals(['foobar'], [...$list]);
    }
}
