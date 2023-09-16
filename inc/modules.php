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
        if (is_string($key)) {
            $newKey = maybePrefix($key, $prefix, '@');
        } else {
            $newKey = $key;
        }

        if ($service instanceof Service) {
            $newSrv = prefixDeps($prefix, $service);
        } else {
            $newSrv = $service;
        }

        yield $newKey => $newSrv;
    }

    if ($module instanceof Generator) {
        $run = $module->getReturn();
        if ($run !== null) {
            return prefixDeps($prefix, $run);
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
