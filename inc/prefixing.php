<?php

declare(strict_types=1);

namespace Mecha\Modules;

/**
 * Prefixes a services's dependencies and actions, recursively.
 *
 * @param string $prefix The prefix to add.
 * @param Service $service The service.
 * @return Service A prefixed copy of the service.
 */
function prefixDeps(string $prefix, Service $service): Service
{
    $newDeps = [];
    foreach ($service->deps as $dep) {
        if ($dep instanceof Service) {
            $newDeps[] = prefixDeps($prefix, $dep);
        } elseif (is_string($dep)) {
            $newDeps[] = maybePrefix($dep, $prefix, '@');
        }
    }

    $newActions = [];
    foreach ($service->actions as $action) {
        $newActions[] = prefixDeps($prefix, $action);
    }

    $newService = clone $service;
    $newService->deps = $newDeps;
    $newService->actions = $newActions;

    return $newService;
}

/**
 * Prefixs a string, unless it starts with a certain character.
 *
 * @param string $str The string to prefix.
 * @param string $prefix The prefix to add.
 * @param string $ignore The character to ignore.
 */
function maybePrefix(string $str, string $prefix, string $ignore): string
{
    if ($str[0] === $ignore) {
        return substr($str, strlen($ignore));
    } else {
        return $prefix . $str;
    }
}
