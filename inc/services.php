<?php

declare(strict_types=1);

namespace Mecha\Modules;

use Psr\Container\ContainerInterface;

/**
 * Creates a PSR-11 service definition that resolves dependencies and invokes a function with their values.
 *
 * @param callable(...mixed):mixed $fn The function to bind.
 * @param list<string|Service> $deps The dependencies to resolve and pass to the function.
 */
function bind(callable $fn, array $deps = []): BoundFn
{
    return new BoundFn($fn, $deps);
}

/**
 * If the given function is bound, returns the original function and its dependencies.
 * Otherwise, it will return the argument and an empty dependency list.
 *
 * @param callable $fn The function to unbind.
 * @return array{callable,list<string|Service>} The original function and its dependencies.
 */
function unbind(callable $fn): array
{
    if ($fn instanceof BoundFn) {
        return [$fn->fn, $fn->deps];
    } else {
        return [$fn, []];
    }
}

/**
 * Wraps a service defintion in a service object, to allow extensions and actions to be attached to it.
 *
 * @param callable(ContainerInterface,...mixed):mixed $fn The service definition.
 */
function service(callable $fn): Service
{
    return new Service($fn);
}

/**
 * Creates a simple bound service definition.
 *
 * @see bind
 * @param callable(...mixed):mixed $fn The factory function.
 * @param list<string|Service> $deps The dependencies to resolve and pass to the factory function.
 */
function factory(callable $callback, array $deps = []): Service
{
    return new Service(bind($callback, $deps));
}

/**
 * Creates a service definition that returns a fixed value.
 *
 * @param mixed $value The service value.
 */
function value($value): Service
{
    return new Service(fn () => $value);
}

/**
 * Creates a service definition that resolves to a global variable's value.
 *
 * @param string $name The name of the global variable.
 */
function globalVar(string $name): Service
{
    return new Service(fn () => $GLOBALS[$name]);
}

/**
 * Creates a service defintion that resolves to a constant's value.
 *
 * @param string $name The name of the constant.
 */
function constValue(string $name): Service
{
    return new Service(fn () => \constant($name));
}

/**
 * Creates a service definition that resolves to an environment variable's value.
 *
 * @param string $name The name of the environment variable.
 */
function env(string $name): Service
{
    return new Service(fn () => $_ENV[$name]);
}

/**
 * Creates a service definition that resolves to another service's value.
 *
 * @param string $id The ID of the service to resolve.
 */
function alias(string $original): Service
{
    return factory(fn ($dep) => $dep, [$original]);
}

/**
 * Creates a service definition that resolves to a string formatted with some dependencies.
 *
 * @param string $template A printf-style template string.
 * @param list<string|Service> $deps The dependencies to resolve and interpolate into the template.
 */
function template(string $template, array $deps = []): Service
{
    return factory(fn () => vsprintf($template, func_get_args()), $deps);
}

/**
 * Creates a service definition that invokes a constructor with some dependencies.
 *
 * @param class-string $class The full-qualified name of the class to instantiate.
 * @param list<string|Service> $deps The dependencies to resolve and pass to the constructor.
 */
function instance(string $class, array $deps = []): Service
{
    return factory(fn () => new $class(...func_get_args()), $deps);
}

/**
 * Creates a service definition that resolves to a callback.
 *
 * @param callable(...mixed):mixed $fn The callback. Recieves invocation args first, then the dependency values.
 * @param list<string|Service> $deps The dependencies to resolve and pass to the callback.
 */
function callback(callable $callback, array $deps = []): Service
{
    return factory(fn (...$deps) => fn (...$args) => $callback(...$args, ...$deps), $deps);
}

/**
 * Creates a service definition that resolves to an array of its dependencies.
 *
 * @param list<string|Service> $deps The dependencies to resolve and return as an array.
 */
function collect(array $deps): Service
{
    return factory(fn () => func_get_args(), $deps);
}

/**
 * Creates a service definition from a PHP file that returns a service definition.
 *
 * @param string $path The path to the PHP file.
 * @param list<string|Service> $deps The dependencies to pass to the service definition.
 */
function load(string $path, array $deps = []): Service
{
    if (empty($deps)) {
        return new Service(require $path);
    } else {
        return new Service(bind(fn () => call_user_func_array(require $path, func_get_args()), $deps));
    }
}

/**
 * Creates a dummy service definition with an attached extension.
 *
 * @param string $id The ID of the service to extend.
 * @param callable(mixed,mixed):mixed $extension A function that takes the DI container and the previous value.
 */
function extend(string $id, callable $extension): Service
{
    return service(fn () => null)->extends($id, $extension);
}

/**
 * Creates a dummy service definition with an attached action.
 *
 * @param callable(mixed,ContainerInterface):void $action A function that takes the DI container.
 */
function run(callable $callback): Service
{
    return service(fn () => null)->runs($callback);
}

/**
 * Creates a function that resolves and invokes a callback service, passing any invocation args and dependencies.
 *
 * @param string $id The ID of the callback service to call.
 * @param list<string|Service> $deps The dependencies to pass to the callback service, following any invocation args.
 */
function invoke(string $id, array $deps = []): BoundFn
{
    // Save the callback ID at the beginning of the dep list
    array_unshift($deps, $id);

    return bind(function () {
        // Get the callback from the beginning of the dep list
        $args = func_get_args();
        $action = array_shift($args);
        // Call it with the other arguments
        return call_user_func_array($action, $args);
    }, $deps);
}

/**
 * Creates an action - a late-running extension that is guaranteed to recieve the final extended value and cannot
 * modify it.
 *
 * @param string $id The ID of the service to extend.
 * @param callable $fn A function that receives the DI container and the value of the `$id` service. Can be the result
 *                     of `bind()`.
 * @param list<string|Service> $deps The dependencies to pass to the callback service.
 */
function action(string $id, callable $fn): Service
{
    return service(fn () => null)->on($id, $fn);
}

/**
 * Creates a wire - an action that runs a callback for every services connect to the wire.
 *
 * @param string $id The ID of the service to attach the wire to.
 * @param callable $fn The function to call for every service connected to the wire. It receives the value of the `$id`
 *                     service and the connected service as args. Can be the result of `bind()`.
 */
function wire(string $id, callable $fn): Service
{
    [$fn, $deps] = unbind($fn);

    return service(fn () => new Wire($fn))
        ->on($id, bind(fn ($prev, Wire $wire, ...$deps) => $wire->trigger($prev, ...$deps), $deps));
}

/**
 * Resolves dependencies from a container.
 *
 * @param ContainerInterface $c The container to use resolution.
 * @param iterable<string|Service> $deps A list of dependencies to resolve.
 * @return list<mixed> The resolved dependency values.
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

function mergeExtensions(array $factories, array $extensions): array
{
    foreach ($extensions as $id => $extension) {
        if (isset($factories[$id])) {
            $factories[$id] = new MergedFactory($factories[$id], $extension);
        }
    }

    return $factories;
}

/** @return list<string|callable(ContainerInterface):mixed> */
function getDeps(callable $service): array
{
    if ($service instanceof Service) {
        $fn = $service->definition;
    } else {
        $fn = $service;
    }

    if ($fn instanceof BoundFn) {
        return $fn->getDeps();
    } else {
        return [];
    }
}
