<?php

declare(strict_types=1);

namespace Mecha\Modules\Tests;

use Mecha\Modules\ExtensionList;
use Mecha\Modules\Stubs\TestContainer;
use PHPUnit\Framework\TestCase;

/** @covers ExtensionList */
class ExtensionListTest extends TestCase
{
    public function test_ctor(): void
    {
        $exts = [
            fn ($c, $p) => $p + 1,
            fn ($c, $p) => $p + 1,
            fn ($c, $p) => $p + 1,
        ];

        $actions = [
            fn ($c, $p) => $this->assertEquals(4, $p, 'Action argument is not the final value'),
            fn ($c, $p) => $this->assertEquals(4, $p, 'Action argument is not the final value'),
        ];

        $list = new ExtensionList($exts, $actions);
        $list(new TestContainer(), 1);
    }

    public function test_add_methods(): void
    {
        $exts = [
            fn ($c, $p) => $p + 1,
            fn ($c, $p) => $p + 1,
            fn ($c, $p) => $p + 1,
        ];

        $actions = [
            fn ($c, $p) => $this->assertEquals(4, $p, 'Action argument is not the final value'),
            fn ($c, $p) => $this->assertEquals(4, $p, 'Action argument is not the final value'),
        ];

        $list = new ExtensionList();
        $list->addExtension($exts[0]);
        $list->addExtension($exts[1]);
        $list->addExtension($exts[2]);
        $list->addAction($actions[0]);
        $list->addAction($actions[1]);

        $list(new TestContainer(), 1);
    }
}
