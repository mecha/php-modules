<?php

declare(strict_types=1);

namespace Mecha\Modules;

/** @param mixed $value */
function value($value): Service
{
    return new Service(fn () => $value);
}

function globalVar(string $name): Service
{
    return new Service(fn () => $GLOBALS[$name]);
}

function constant(string $name): Service
{
    return new Service(fn () => \constant($name));
}

function alias(string $original): Service
{
    return new Service(fn ($deps) => $deps->current(), [$original]);
}

/** @param list<string|Service> $deps */
function instance(string $class, array $deps = []): Service
{
    return new Service(fn ($deps) => new $class(...$deps), $deps);
}

/** @param list<string|Service> $deps */
function factory(callable $callback, array $deps = []): Service
{
    return new Service(fn ($deps) => call_user_func_array($callback, [...$deps]), $deps);
}

/** @param list<string|Service> $deps */
function callback(callable $callback, array $deps = []): Service
{
    return new Service(fn ($deps) => fn (...$args) => $callback(...$deps, ...$args), $deps);
}

/** @param list<string|Service> $deps */
function collect(array $deps): Service
{
    return new Service(fn ($deps) => [...$deps], $deps);
}

function extend(Service $extension): Service
{
    return new Service(fn ($deps, $c, $p) => $extension($p, $deps, $c));
}

function run(callable $callback, array $deps = []): Service
{
    return callback($callback, $deps)->then(fn (callable $callback) => $callback());
}

/** @param list<string|Service> $deps */
function load(string $path, array $deps): Service
{
    return new Service(function ($deps, $c, $p) use ($path) {
        $fn = require $path;
        assert(is_callable($fn));
        return call_user_func_array($fn, [...$deps]);
    }, $deps);
}

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
