# PHP Modules

A system for assembling a PHP application from a series of modules.

⚠ **This package is still a work in progress**.

# Table of Contents

* [Motivation](#motivation)
* [Modules](#modules)
    * [Service Conventions](#service-conventions)
        * [1. Non-String IDs](#1.-non-string-ids)
        * [2. Extensions: Same-ID Conflicts](#2.-extensions%3A-same-id-conflicts)
        * [3. Run Actions](#3.-run-actions)
    * [Service Helpers](#service-helpers)
        * [Reference](#reference)
        * [Inline Dependencies](#inline-dependencies)
        * [Run Actions](#run-actions)
    * [Module Scoping](#module-scoping)
* [Modular Applications](#modular-applications)
* [Full Example](#full-example)
* [License](#license)

# Motivation

The need for a module system that is compliant with PSR-7 containers and is designed around module conventions rather
than relying on a specific implementation. However, this package _does_ still offer an implementation.

In this context, a module is an application "slice"; a self-enclosed unit that contains all the necessary code for
one of an application's systems. A "modular application" is an application that utilizes modules. A core motivation
for this module system is the ability to compose an application out of nothing _but_ modules. To that end, the module
system would need to have a very small footprint, so as to not completely overtake an application's architecture with
leaky abstractions or introduce a lot of obscurity.

# Modules

A module is any `iterable` of PSR-7 service definitions, keyed by their ID:

```php
// Arrays
$loggerModule = [
    'log_file' => fn() => 'log.txt',
    'logger' => fn(ContainerInterface $c) => new Logger($c->get('log_file')),
];

// Iterators
$loggerModule = new SomeFancyIterator();

// Generators work too!
function loggerModule() {
    yield 'log_file' => fn() => 'log.txt';
    yield 'logger' => fn(ContainerInterface $c) => new Logger($c->get('log_file'));
};
```

## Service Conventions

### 1. Non-String IDs

By convention, any service that is mapped to a non-string key will have a random unique string ID generated for it.

The utility of a service with an unknown generated ID may not be immediately obvious. However, we will see later how
this can be useful when using [run actions with service helpers](#run-actions).

### 2. Extensions: Same-ID Conflicts

Services with the same ID get "merged" into a single service through a middleware-esque mechanism, where the resolved
value of the first service is provided to the second, whose value gets provided to the third, and so on until all
services that share that ID are called.

A service that gets merged into a previous service with the same ID is called an "extension". Also, extension happens 
regardless of whether the services come from the same module.

For example:

```php
$module1 = [
    'list' => fn() => [1, 2, 3]
];

$module2 = [
    'list' => fn(ContainerInterface $c, $prev) => [...$prev, 4, 5]
];

$module3 = [
    'list' => fn(ContainerInterface $c, $prev) => array_map(fn($n) => $n * 2, $prev)
];
```

In the above case, the expected value of `$c->get('list')` would be `[2, 4, 6, 8, 10]`.

Note that in the above example, the order in which the modules are loaded directly affects what the value of the `list`
service ends up being. Modules are ideally authored to be order-agnostic, but these details are left up to the modular
application to figure out.

### 3. Run Actions

Modules often need to ability to run some initialization code, not just provide services to the modular application.
For instance, a module may need to set up a router, create some event listeners, register some data, hook into a
framework, an so on.

This is where "run actions" come in. A module may provide callbacks to the modular application, that can be run
whenever the application is ready to "run" the modules.

There are 2 ways for a module to provide run actions.

1. If the module is a generator, it can supply a run action by returning a callback that recieves the DI container,
similar to a service definition. Note the use of `return` vs `yeild`:

```php
function myModule() {
    yield 'message' => fn() => "Greetings human!\n",
    
    return function($c) {
        echo $c->get('message');
    };
}
```

2. If the module uses service helpers, run actions can be provided by service definitions. This is covered in more
detail in the [Service Helpers > Run Actions](#run-actions) section.

## Service Helpers

This package provides a number of service helper functions that add features to service definitions.

These helper functions focus on making it easier to write services for specific purposes, and return valid
PSR7-compliant service definitions. For example, the below 3 service definitions are functionally equivalent:

```php
// Raw service definition
[
    'log_file' => fn(ContainerInterface $c) => $c->get('log_dir') . '/log.txt',
]

// Using the `factory()` helper
[
    'log_file' => factory(fn(string $dir) => "$dir/log.txt", ['log_dir']),
]

// Using the `template()` helper
[
    'log_file' => template("%s/log.txt", ['log_dir']),
]
```

Many of the helpers perform automatic dependency resolution, as can be seen in the above example.

The `factory()` and `template()` helpers accept a list of service IDs as a second argument. These IDs will be resolved
from the container, via `$c->get(...)`, and the values are provided to the factory function and template string
respectively. Most of the other available helpers also provide this convenience.

Here is a full reference of each service helper:

### Reference

#### `factory()`

This generic helper can be used for creating any kind of service. Its arguments are:

1. A factory function that recieves the resolved dependencies and the container,
1. A list of the dependency services or their IDs.

Example:

```php
[
    'db_conn' => factory(
        fn($dsn, $user, $pass) => new PDO($dsn, $user, $pass),
        ['db_dsn', 'db_user', 'db_pass']
    ),
    'db_dsn' => ...,
    'db_user' => ...,
    'db_pass' => ...,
]
```

#### `template()`

This helper can be used for services that use other services to compile string values. Its arguments are:

1. A printf-style template string,
1. A list of the dependency services or their IDs.


Example:

```php
[
    'db_dsn' => template('mysql:host=%s;dbname=%s', ['db_host', 'db_name']),
    'db_host' => ...,
    'db_name' => ...,
]
```

#### `instance()`

This helper can be used for services that create instances of classes using their dependencies. Its arguments are:

1. A fully-qualified class name string,
1. A list of the dependency services or their IDs.

This helper will call the constructor of the specified class with the resolved dependency values.

Example:

```php
[
    'db_conn' => instance(PDO::class, ['db_dsn', 'db_user', 'db_pass']),
    'db_dsn' => ...,
    'db_user' => ...,
    'db_pass' => ...,
]
```

#### `callback()`

This helper can be used for services that return callbacks. Its arguments are:

1. The callback function, which gets the dependencies as arguments followed by its invocation arguments,
1. A list of the dependency services or their IDs.

Example:

```php
[
    'calculate' => callback(
        fn($num, $offset, $mult) => ($num + $offset) * $mult
        ['offset', 'multiplier']
    ),
    'offset' => fn() => 2,
    'multiplier' => fn() => 5,
]

$fn = $c->get('calculate');
$fn(6); // => (6 + 2) * 5 => 40
```

#### `value()`

This helper can be used for services that simply return a constant value. Its only argument is the value.

Example:

```php
[
    'db_host' => value('localhost'),
    'db_name' => value('my_db'),
    'user_cols' => value(['id', 'name', 'email', 'role']),
]
```

#### `collect()`

This helper can be used to create a service that simply returns its dependencies. Its only argument is the list of
services or their IDs.

Example:

```php
[
    'db_tables' => collect([
        'users_table',
        'posts_table',
        'comments_table',
    ]),
    'users_table' => ..., 
    'posts_table' => ..., 
    'comments_table' => ..., 
]
```

#### `alias()`

This helper can be used to create aliases to other services. Its only argument is the ID of the service to alias.

Example:

```php
[
    'original' => ...,
    'alias' => alias('original'),
]
```

#### `constValue()`

This helper can be used to create services that return the value of a [constant][php-constants] defined with `const`
or `define()`. Its only argument is the constant's name.

Example:

```php
define('USING_DEFINE', 'foo');

namespace Foo {
    const USING_CONST = 'bar';
}

[
    'const_1' => constValue('USING_DEFINE'),
    'const_2' => constValue('Foo\\USING_CONST'),
]
```

#### `globalVar()`

This helper can be used to create services that return the value of a global variable. Its only argument is
the global variable's name.

Example:

```php
global $myVar;

[
    'my_var' => globalVar('my_var'),
]
```

#### `env()`

This helper can be used to create services that return the value of an environment variable. Its only argument is
the name of the environment variable.

Example:

```php
[
    'db_pass' => env('DB_PASS'),
]
```

#### `load()`

This helper can be used to load a service definition from a file. Its arguments are:

1. The path to a PHP file that returns a service definition function,
1. A list of the dependency services or their IDs.

Example:

```php
// my-service.php

return function($foo, $bar) {
    return new Baz($foo, $bar->getSomething());
};
```

```php
// module.php
[
    'baz' => load('my-service.php', ['foo', 'bar']),
    'foo' => ...,
    'bar' => ...,
]
```

#### `extend()`

This helper is very similar to the `factory()` helper, with one difference: the factory function receives the previous
value before the dependencies as argument.

It is intended to be used for extension services that need to extend previous service value.

Example:

```php
$module1 = [
    'tables' => collect([
        'users_table',
        'posts_table',
        'comments_table',
    ]),
];

$module2 = [
    'tables' => extend(
        fn(array $tables, $newTable) => [...$tables, $newTable],
        ['new_table'],
    ),
    'new_table' => ...,
];
```

### Inline Dependencies

Service helpers that accept a list of dependencies are not limited to only recieving string IDs. Service definitions
may also be provided.

Example:

```php
$module = [
    'logger' => instance(Logger::class, [
        template("%s/log.txt", ['log_dir'])
    ]),
    'log_dir' => value('/var/log/my_app'),
]
```

In the above example, we are able to utilize the `instance()` helper even though the log file is not declared as its
own service. We instead embed an inline dependency that generates the log file value from the existing `log_dir`
service by using the `template()` helper.

Inline dependencies reduce the need to create services that only exist to be dependencies for 1 other service; a
"problem" that is often encountered when using these service helpers.

### Run Actions

Service helpers can have [run actions](#3.-run-actions) associated with them. This is done using the `then()` chaining
method.

Consider the below example:

```php
[
    'log_file' => value('log.txt'),
    'logger' => instance(Logger::class, ['log_file'])
                  ->then(fn($logger) => $$logger->clear(), [...]);
]
```

The `then()` takes a callback function and a list of dependencies as arguments, similar to the `factory()` helper.
However, unlike the `factory()` helper, the callback function will always recieve its own service value as the first
argument. The dependencies start from the 2nd argument, and the last argument is always the container.

```php
[
    'foo' => factory(...)
              ->then(fn($foo, $dep1, $dep2, $c) => ..., ['dep1', 'dep2']),
]
```

The callback function given to `then()` will be run by the modular application as a run action.

This allows service definitions to provide some initialization code that is run after the application has loaded all
of its modules.

You may recall that [generator modules can return run actions](#3.-run-actions). Non-generator modules can provide
run actions that are not associated with any service using the `run()` service helper.

It shares the same signature as the [`callback()`](#callback) helper.

```php
$module = [
    'users_table' => instance(UsersTable::class),
    run(fn($table) => $table->create(), ['users_table'])
];
```

In fact, `run()` is equivalent to `callback(...)->then(fn($cb) => $cb())`.

You'll also notice that no ID was provided to the `run()` service. This means that the service will have a unique
ID generated for it. Since we don't care about being able to `get()` this service from the container (we only want it
to be run), this actually works in our favour. We don't need to come up with names for things that don't need them.

[php-constants]: https://www.php.net/manual/en/language.constants.syntax.php

### Module Scoping

Another benefit of using the service helper functions is that they record each service's dependencies. This is because
the returned service definitions from the helper functions area actually objects that are invocable via `__invoke()`,
and save their dependencies as a public property:

```php
$service = instance(Foo::class, ['bar', 'baz']);
$service->deps // ['bar', 'baz']
```

This enables the application to manipulate a module's services if needed.

One such useful manipulation is scoping: where all of a module's services are prefixed by some string to ensure that
no ID conflicts occur unintentionally. This is only possible if the dependencies can also be renamed.

Scoping is done using the `scope()` function. For example:

```php
$module = [
    'foo' => instance(Foo::class, ['bar']),
    'bar' => value('hello'),
];

$sModule = scope('mod/', $module);
```

This creates a module that is equivalent to the below:

```php
$module = [
    'mod/foo' => instance(Foo::class, ['mod/bar']),
    'mod/bar' => value('hello'),
];
```

_Note that the use of `/` as a scoping delimiter is not enforced. You can use any separator you like._

Modules can prefix dependency IDs with a `@` character to signify that the dependency should not be prefixed by the
application. This is often because the dependency is expected to be provided by another module.

```php
$module1 = scope('mod1/', [
    'logger' => instance(Logger::class, ['@log_file']),
]);

$module2 = scope('mod2/', [
    'log_file' => value('log.txt'),
]);
```

The scoped `$module1` in the above example becomes:

```php
$module1 = [
    'mod1/logger' => instance(Logger::class, ['log_file']),
];
```

Here are some reasons why you may want to consider scoping:

1. No unintentional ID conflicts.  
All service IDs are prefixed, removing the need to worry about ID conflicts.

1. External dependencies are explicitly marked with a `@` character.  
This makes it clearer when a module uses a service that it itself does not provide, reducing obscurity.

1. No inter-module extensions.  
If all modules are scoped by the application, modules cannot extend services from other modules since doing so would
require knowing what prefix the application is using for its modules. This forces modules to extend using
application-level slots.  
Rather than extending another module's `log_file` service, your module would instead extend `@logger/log_file`, for
example. This allows modules to be decoupled from each other, while explicitly expecting the application to have some
module, any module, mapped to the `logger/` prefix that provides a `log_file` service.  
This subtle difference makes working with many modules much easier.

# Modular Applications

For an application to utilize modules, it only needs to iterate over a module list, collect their service definitions
and run actions, add the service definitions to a DI container, and finally run the run actions using the container.

The simplest way to do this is to use this package's `App` class.

```php
$databaseModule = [...];
$loggerModule = [...];

$app = new App([
    scope('db/', $databaseModule),
    scope('logger/', $loggerModule),
]);

$app->addModule([
    ...
]);

$app->run();
```

The `App` class accepts a container factory function as a second argument, should you need to supply your own container
implemention. The factory function recieves an associative array of all the services collected from all the modules.

```php
$app = new App([...], fn($services) => new SpecialContainer($services));
```

If you need more control, you can bypass using the `App` class altogether, provided that you adhere to the
[service conventions](#service-conventions). If you do not need to modify how module services get prepared for the
DI contianer, you can use the `Psr7Compiler` class to compile modules into a list of PSR7-compliant services and a list
of run actions.

Example:

```php
$modules = [...];
$compiler = new Psr7Compiler($modules);

$compiler->addModule([
    ...
]);

$factories = $compiler->getFactories(); // Give these to your DI container
$actions = $compiler->getActions(); // Run these functions after all modules are loaded
```

# Full Example

Here is an example of a demo application that parses an expression from STDIN and evaluates it. The implementation code
for the application's functionality is omitted from the example for brevity. The example focus on demonstrating
various aspects of the module system, including:

1. Generator modules with argumuents
1. Run actions
1. Custom service helpers
1. Module scoping

----

The core logic module:

```php
function coreModule(string $input) {
    yield 'parser' => instance(Parser::class, [
        value($input)
    ]);

    yield 'calculator' => instance(Calculator::class, ['operators']);
    yield 'operators' => value([]); // Empty for now - will be extended by other modules

    yield run(function($calc, $parser) {
        $expr = $parser->parse();
        echo $calc->eval($expr);
    }, ['calculator', 'parser']);
}
```

A custom service helper:

```php
function operator(string $pattern, callable $evalFn) {
    // Create an operator instance
    return instance(Operator::class, [value($pattern), callback($evalFn)])
            // Register the operator to the `operators` service
            ->then(fn($self, $ops) => [...$ops, $self], ['@core/operators']));
}
```

A couple of modules that extend the core module via the custom helper:

```php
$basicOps = [
    'add' => operator('? + ?', fn ($a, $b) => $a + $b),
    'sub' => operator('? - ?', fn ($a, $b) => $a - $b),
    'mul' => operator('? * ?', fn ($a, $b) => $a * $b),
    'div' => operator('? / ?', fn ($a, $b) => $a / $b),
];
```

```php
$trigOps = [
    'sin' => operator('sin(?)', fn($n) => sin($n)),
    'cos' => operator('cos(?)', fn($n) => cos($n)),
    'tan' => operator('tan(?)', fn($n) => tan($n)),
];
```

And finally, the application:

```php
$input = fgets(STDIN);

$app = new App([
    scope('core/', coreModule($input)),
    scope('ops/basic', $basicOps),
    scope('ops/trig', $trigOps),
]);

$app->run();
```

# License

This project is licensed under the [MIT License](./LICENSE).

Copyright © 2023 Miguel Muscat
