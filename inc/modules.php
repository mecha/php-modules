<?php

declare(strict_types=1);

namespace Mecha\Modules;

function scope(string $prefix, iterable $module): iterable
{
    foreach ($module as $key => $service) {
        $newKey = is_string($key) ? $prefix . $key : $key;
        $newSrv = $service->prefixDeps($prefix);
        yield $newKey => $newSrv;
    }

    $run = $module->getReturn();

    if ($run !== null) {
        return $run->prefixDeps($prefix);
    } else {
        return null;
    }
}

/**
 * Group a list of modules into a single module.
 *
 * @param iterable<int,iterable<mixed,callable>> $modules
 */
function group(iterable $modules): iterable
{
    foreach ($modules as $module) {
        yield from $module;
    }
}
