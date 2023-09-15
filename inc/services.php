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

function constValue(string $name): Service
{
    return new Service(fn () => \constant($name));
}

function env(string $name): Service
{
    return new Service(fn () => $_ENV[$name]);
}

function alias(string $original): Service
{
    return new Service(fn ($deps) => $deps[0], [$original]);
}

function template(string $template, array $deps = []): Service
{
    return new Service(fn ($deps) => vsprintf($template, $deps), $deps);
}

/** @param list<string|Service> $deps */
function instance(string $class, array $deps = []): Service
{
    return new Service(fn ($deps) => new $class(...$deps), $deps);
}

/** @param list<string|Service> $deps */
function factory(callable $callback, array $deps = []): Service
{
    return new Service(fn ($deps) => call_user_func_array($callback, $deps), $deps);
}

/** @param list<string|Service> $deps */
function callback(callable $callback, array $deps = []): Service
{
    return new Service(fn ($deps) => fn (...$args) => $callback(...$args, ...$deps), $deps);
}

/** @param list<string|Service> $deps */
function collect(array $deps): Service
{
    return new Service(fn ($deps) => $deps, $deps);
}

/** @param list<string|Service> $deps */
function extend(callable $extension, array $deps = []): Service
{
    return new Service(fn ($deps, $c, $p) => $extension($p, ...$deps));
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

