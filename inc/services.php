<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

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
    return new Service(fn ($c, $p, $deps) => resolveDeps($c, $deps)[0], [$original]);
}

function template(string $template, array $deps = []): Service
{
    return new Service(fn ($c, $p, $deps) => vsprintf($template, resolveDeps($c, $deps)), $deps);
}

/** @param list<string|Service> $deps */
function instance(string $class, array $deps = []): Service
{
    return new Service(fn ($c, $p, $deps) => new $class(...resolveDeps($c, $deps)), $deps);
}

/** @param list<string|Service> $deps */
function factory(callable $callback, array $deps = []): Service
{
    return new Service(fn ($c, $p, $deps) => call_user_func_array($callback, resolveDeps($c, $deps)), $deps);
}

/** @param list<string|Service> $deps */
function callback(callable $callback, array $deps = []): Service
{
    return new Service(fn ($c, $p, $deps) => fn (...$args) => $callback(...$args, ...resolveDeps($c, $deps)), $deps);
}

/** @param list<string|Service> $deps */
function collect(array $deps): Service
{
    return new Service(fn ($c, $p, $deps) => resolveDeps($c, $deps), $deps);
}

/** @param list<string|Service> $deps */
function extend(callable $extension, array $deps = []): Service
{
    return new Service(fn ($c, $p, $deps) => $extension($p, ...resolveDeps($c, $deps)), $deps);
}

function run(callable $callback, array $deps = []): Service
{
    return callback($callback, $deps)->then(fn (callable $callback) => $callback());
}

/** @param list<string|Service> $deps */
function load(string $path, array $deps): Service
{
    return new Service(function ($c, $p, $deps) use ($path) {
        $fn = require $path;
        assert(is_callable($fn));
        return call_user_func_array($fn, resolveDeps($c, $deps));
    }, $deps);
}

/**
 * @param iterable<string|Service> $deps
 * @return list<mixed>
 */
function resolveDeps(ContainerInterface $c, iterable $deps): array
{
    $result = [];

    foreach ($deps as $k => $dep) {
        $result[$k] = ($dep instanceof Service)
            ? $dep($c)
            : $c->get($dep);
    }

    return $result;
}
