<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Generator;

/**
 * Scope a module by prefixing its service IDs.
 *
 * @param iterable<mixed,callable> $module
 */
function scope(string $prefix, iterable $module): iterable
{
    foreach ($module as $key => $service) {
        $newKey = is_string($key) ? $prefix . $key : $key;
        $newSrv = $service->prefixDeps($prefix);
        yield $newKey => $newSrv;
    }

    if ($module instanceof Generator) {
        $run = $module->getReturn();
        if ($run !== null) {
            return $run->prefixDeps($prefix);
        }
    }

    return null;
}

/**
 * Scopes a series of modules by prefixing their service IDs using the keys they are mapped to in the iterable.
 *
 * @param string $delimiter The delimiter to use between the key and the service ID.
 * @param iterable<string,iterable<mixed,callable>> $modules An associative iterable of modules.
 */
function scopeAssoc(string $delimiter = '.', iterable $modules): iterable
{
    foreach ($modules as $key => $module) {
        yield scope($key . $delimiter, $module);
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
