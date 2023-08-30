<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\Service;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;
use stdClass;

class ServiceTest extends TestCase
{
    /** @covers Service::resolveDep */
    public function test_resolveDep_string(): void
    {
        $cntr = new TestContainer(['foo' => 1, 'bar' => 2]);
        $actual = Service::resolveDeps($cntr, ['foo', 'bar']);

        $this->assertEqualsCanonicalizing(['foo' => 1, 'bar' => 2], [...$actual]);
    }

    /** @covers Service::__invoke */
    public function test_invoke(): void
    {
        $cntr = new TestContainer(['foo' => 1, 'bar' => 2]);

        $expected = new stdClass();
        $service = new Service(function ($deps) use ($expected) {
            $this->assertEqualsCanonicalizing([1, 2], [...$deps]);
            return $expected;
        }, ['foo', 'bar']);

        $actual = $service($cntr);

        $this->assertSame($expected, $actual);
    }

    /** @covers Service::prefixDeps */
    public function test_prefixDeps(): void
    {
        $service = new Service(fn() => null, ['foo', 'bar', '@baz']);

        $actual = $service->prefixDeps('prefix/')->deps;
        $expected = ['prefix/foo', 'prefix/bar', '@baz'];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
