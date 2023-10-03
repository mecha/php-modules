<?php

declare(strict_types=1);

namespace Mecha\Modules;

use LogicException;

/**
 * Prefixes a services's dependencies and actions, recursively.
 *
 * @param string $prefix The prefix to add.
 * @param callable $service The service.
 * @return callable A prefixed copy of the service.
 */
function prefixDeps(string $prefix, callable $service): callable
{
    $fn = ($service instanceof Service)
        ? $service->definition
        : $service;

    if ($fn instanceof BoundFn) {
        $deps = [];
        foreach ($fn->getDeps() as $i => $dep) {
            if (is_string($dep)) {
                $deps[] = maybePrefix($dep, $prefix, '@');
            } elseif (is_callable($dep)) {
                $deps[] = prefixDeps($prefix, $dep);
            } else {
                $type = is_object($dep) ? get_class($dep) : gettype($dep);
                throw new LogicException("Invalid dependency: [#$i] $type.");
            }
        }

        $fn = $fn->withDeps($deps);
    }

    if ($service instanceof Service) {
        $extensions = [];
        foreach ($service->extensions as $id => $extension) {
            $newId = maybePrefix($id, $prefix, '@');
            $extensions[$newId] = prefixDeps($prefix, $extension);
        }

        $actions = [];
        foreach ($service->actions as $id => $action) {
            $newId = maybePrefix($id, $prefix, '@');
            $actions[$newId] = prefixDeps($prefix, $action);
        }

        $callbacks = [];
        foreach ($service->callbacks as $callback) {
            $callbacks[] = prefixDeps($prefix, $callback);
        }

        $newService = new Service($fn);
        $newService->extensions = $extensions;
        $newService->actions = $actions;
        $newService->callbacks = $callbacks;
    } else {
        $newService = $fn;
    }


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
