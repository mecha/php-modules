# PHP Modules

A system for assembling a PHP application from a series of reusable modules.

⚠ **This package is still a work in progress**.

# Table of Contents

- [Motivation](#motivation)
- [Getting Started](#getting-started)
- [Modules](#modules)
- [Services](#services)
  - [Binding](#binding)
  - [Wrapping](#wrapping)
    - [factory](<#factory()>)
    - [instance](<#instance()>)
    - [callback](<#callback()>)
    - [template](<#template()>)
    - [value](<#value()>)
    - [alias](<#alias()>)
    - [collect](<#collect()>)
    - [env](<#env()>)
    - [constValue](<#constvalue()>)
    - [globalVar](<#globalvar()>)
    - [load](<#load()>)
    - [invoke](<#invoke()>)
  - [Extensions](#extensions)
  - [Actions](#actions)
  - [Wires](#wires)
  - [Run callbacks](#run-callbacks)
- [Scoping](#scoping)
- [Advanced Setup](#advanced-setup)
- [License](#license)

# Motivation

The need to build applications in "slices", or modules, that facilitate the wiring of application components. The module
system must utilize PSR-11 DI containers to ensure interoperability, and is ideally compatible with
[Dhii modules](https://github.com/Dhii/module-interface).

For all intents an purposes, this is a revision of the Dhii module spec (co-authored by your truly). A big difference
here is that the module system provides all the required scaffolding to get a modular application up and running without
the to implement anything, as opposed to a spec that only provides the interface for modules.

Simultaneously, everything is optional. The system is built to work with the bare-minimum: arrays of PSR-11 service
definitions. Extra features are included if you choose to make use of them. You may provide your own DI container,
compile modules yourself, and build your own module system.

# Getting Started

Install with Composer

```
composer require mecha/modules
```

Create an app and give it your modules:

```php
use Mecha\Modules\App;

$app = new App([
    $module1,
    $module2,
]);

$app->addModules([
    $module3,
    $module4,
]);
```

Run your app:

```php
$app->run();
```

By default, the `App` will use the default DI container and compiler implementations. If your need a more bespoke or
customized setup, see the [Advanced Setup](#advanced-setup) section.

# Modules

Modules are any **iterable** (arrays, iterators, and generators) value of PSR-11 service definitions.

**Array example:**

```php
$module = [
    'greeter' => function(ContainerInterface $c) {
        return new Greeter($c->get('message'));
    },
    'message' => fn() => 'Hello world',
];
```

**Generator example:**

```php
function module() {
    yield 'greeter' => function(ContainerInterface $c) {
        return new Greeter($c->get('message'));
    };

    yield 'message' => fn() => 'Hello world';
];
```

The service definitions provided by a module will be processed and registered into a DI container. The order in which
the modules are loaded will settle any ID conflicts across modules.

If you wish to avoid all ID conflicts, consider [scoping](#scoping) your modules.

# Services

While a module _can_ provide plain old PSR-11 service definitions, it is recommend to use the service functions
provided by this package in order to take full advantage of the module system.

## Binding

Service binding is when we rewrite a service definition to accept its dependencies as arguments, rather than just a
PSR-11 container.

We can do this using the `bind()` function:

```php
use function Mecha\Modules\bind;

$before = function(ContainerInterface $c) {
    return new Greeter($c->get('message'), $c->get('name'));
};

$after = bind(function (string $message, string $name) {
    return new Greeter($message, $name));
}, ['message', 'name'])
```

More specifically, the `bind()` function drops the 1st argument (the container), keeps any other arguments, and adds
the resolved dependencies at the end of the argument list. Dependencies are always passed _after_ arguments. This will
be important later.

Since the result of `bind()` is a normal PSR-11 service definition, the result can be used with many of the other
service functions provided by the module system.

## Wrapping

Some of the module system's features require additional information to be optionally attached to service definitions.
To achieve this, we can wrap service definition functions in [invocable objects](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke).

This is done using the `service()` function, which simply takes the function to be wrapped.

You won't usually need to call this function directly. Almost all of the other service functions will wrap you service
definitions for you.

### `factory()`

This is the simplest wrapper. It creates a wrapped bound definition for any function you provide:

```php
use function Mecha\Modules\factory;

$module = [
    'message' => fn() => 'Hello world',
    'greeter' => instance(
        fn(string $message) => new Greeter($message),
        ['message']
    ),
];
```

### `instance()`

This is a more specialized form of `factory()` that facilitates constructing instances, with the dependencies as
constructor arguments.

```php
use function Mecha\Modules\instance;

$module = [
    'message' => fn() => 'Hello world',
    'greeter' => factory(Greeter::class, ['message']),
];
```

### `callback()`

Another more specialized form of `factory()` that is equivalent to giving `factory()` a function that returns another
function. The result is a wrapped, bound service definition that returns a function.

```php
factory(
    function($dep1, $dep2) {
        return function($arg1, $arg2) use ($dep1, $dep2) {
            /*...*/
        };
    },
    ['dep1', 'dep2']
);

// Equivalent to:

callback(
    function($arg1, $arg2, $dep1, $dep2) {
        /*...*/,
    },
    ['dep1', 'dep2']
);
```

Example:

```php
use function Mecha\Modules\callback;

$module = [
    'format' => fn() => 'Hello %s',
    'greeter' => callback(
        fn($arg, $format) => sprintf($format, $arg),
        ['format']
    ),
];
```

The function given to `callback()` will recieve the arguments given to the resulting function first, followed by the
dependencies, if the service has any.

For instance, in the above example, `greeter` takes 2 arguments, but is bound to the `format` dependency. This means
that the resulting function will take 1 argument when invoked:

```
$greeter = $container->get('greeter');
greeter('John'); // Output: "Hello John"
```

### `template()`

A specialized variation of `factory()` that is focused on combining services together into a string using a
printf-style template.

```php
use function Mecha\Modules\template;

$module = [
    'name' => fn() => 'Neo',
    'ammo' => fn() => 999,
    'message' => template('%s has %d rounds left.', ['name', 'ammo']),
];
```

### `value()`

This function creates a wrapped service definition that resolves to a fixed value.

```php
use function Mecha\Modules\value;

$module = [
    'format' => value('Hello %s'),
];
```

**Possible "gotcha"**

Using `value()` is almost equivalent to `service(fn() => 'Hello %s')`. However, to use `value()` you will need to have
the resolved value before the service definition is created.

Consider the below:

```php
use function Mecha\Modules\value;

$module = [
    'using_value' => value(calculate_value()),
    'using_fn' => fn() => calculate_value(),
];
```

The above code will cause `calculate_value()` to be invoked, prior to loading the module into the application.

It is generally recommended to only use the `value()` function with simple values that don't need to be computed.

### `alias()`

Creates a wrapped, bound service definition that simply resolves to its single dependency.

```php
use function Mecha\Modules\alias;

$module = [
    'person' => fn() => 'Thomas Anderson',
    'chosen_one' => alias('person'),
];
```

### `collect()`

Creates a wrapped, bound service definition that resolves to an array that contains its dependencies.

```php
use function Mecha\Modules\collect;

$module = [
    'admin' => fn() => 'GLaDOS',
    'tester' => fn() => 'Chell'
    'assistant' => fn() => 'Wheatley',

    'everyone' => collect(['admin', 'tester', 'assistant']),
];
```

### `env()`

Creates a wrapped service definition that resolves to an environment variable.

```php
use function Mecha\Modules\env;

$module = [
    'editor' => env('EDITOR'),
];
```

### `constValue()`

Creates a wrapped service definition that resolves to constant.

```php
use function Mecha\Modules\constValue;

define('DEV_MODE', false);

$module = [
    'dev_mode' => constValue('DEV_MODE'),
];
```

Remember that constants that are defined using the `const` keyword are implicitly namespaced!

```php
use function Mecha\Modules\constValue;

namespace Foo {
    const BAR = 123;
}

$module = [
    'foo_bar' => constValue('Foo\\BAR'),
];
```

### `globalVar()`

Creates a wrapped service definition that resolves to a global variable.

```php
use function Mecha\Modules\globalVar;

global $user;

$module = [
    'user' => globalVar('user'),
];
```

Note that created service definition will capture the value of the global variable at the time of its first invocation.
If you are a caching DI container, such as the included one, then re-assignment of the global variable won't be
reflected by the service. Direct mutations, however, should still be reflected.

### `load()`

Creates a wrapped service definition that loads a service definition that is returned by a PHP file.

If no dependencies are given, the function in the specified will recieve the DI container:

```php
// my-service.php
return function(ContainerInterface $c) {
    /*...*/
};

// module.php
$module = [
    'foo' => load('my-service.php'),
];
```

If dependencies are given, the function returned from the file is expected to be in bound-form:

```php
// my-service.php
return function ($dep1, $dep2) {
    /*...*/
};

// module.php
$module = [
    'foo' => load('my-service.php', ['dep1', 'dep2']),
];
```

### `invoke()`

Creates a wrapped, bound service definition that gets another service by its ID, and calls its resolved value.

```php
$module = [
    'print_msg' => callback(fn($name) => printf('Hello, %s I am', $name)),

    'name' => value('Yoda'),
    'the_msg' => invoke('print_msg', ['name']),
];
```

The above is equivalent to:

```php
$module = [
    'print_msg' => callback(fn($name) => printf('Hello, %s I am', $name)),

    'name' => value('Yoda'),
    'the_msg' => factory(fn($print, $name) => $print($name), ['print_msg', 'name']),
];
```

## Extensions

An extension is a service definition that modifies the resolved value of another service.

Extension definitions take the DI container as argument - just like service definitions - but also accept a second
`$previous` argument. This argument will hold the resolved value of the service being extended.

The return value of the extension definition will become the new value for the original service.

Extensions can be created using the `extend()` function.

```php
$module = [
    'footer' => value('Thanks for visiting my ugly blog'),

    extend('footer', function(ContainerInterface $c, $footer) {
        return "$footer | Copyright 2013";
    }),
];
```

Extensions declared in this way are typically anonymous. But you can include a key if you'd like!

Remember that you can use `bind()` in place of any service definition:

```php
$module = [
    'footer' => value('Thanks for visiting my ugly blog'),

    extend('footer', bind(
        fn ($footer, $year) => "$footer | Copyright 2013",
        ['year']
    )),
];
```

Extensions can also be attached to wrapped services using the `extends()` method:

```php
$module = [
    'list' => instance(AnimalList::class),
    'item' => value('Pumba')->extends('list', function ($c, $list) {
        $list->add($c->get('item'));
        return $list;
    }),
];
```

If you use `bind()` here, the service's own value will be added as the last dependency:

```php
$module = [
    'list' => instance(AnimalList::class),
    'item' => value('Pumba')->extends('list', bind(function ($list, $self) {
        $list->add($self);
        return $list;
    }),
];
```

## Actions

Actions are a solution to a problem. Consider the below scenario:

We have a module that provides a list of users, and extends the list so that when it gets created, each user in the
list gets their preferences loaded. Note how the extension does not actually extend the list.

Then we have a second extension that adds some users to the list.

```php
$module = [
    'users' => instance(List::class),

    extend('list', bind(function(List $users) {
        foreach ($users as $user) {
            $user->loadPreferences();
        }

        return $users;
    }),

    extend('list', bind(function(List $list) {
        $list->add(new User('Abigail'));
        $list->add(new User('Britney'));
        $list->add(new User('Chrissy'));
        return $list;
    }),
];
```

This, unfortunately, won't have the expected outcome. Extensions are loaded in the provided order, which means that the
first extension will recieve an empty list. The second list will then add the users, but by that point it would be too
late.

To fix this, we'll need to reorder the extensions in this module. However, if our services are not all located in a
single module, managing order can be a nightmare.

---

This is where actions come in.

Actions are simply extensions whose return value is ignored and are run _after_ regular extensions. This guarantees
the actions recieve the final value of the extended service, and that no other action will modify its value.

They are created using the `action()` function.

```php
use function Mecha\Modules\action;

$module = [
    'users' => instance(List::class),

    action('users', function($c, $users) {
        foreach ($users as $user) {
            $user->loadPreferences();
        }
    }),

    extend('list', bind(function(List $list) {
        $list->add(new User('Abigail'));
        $list->add(new User('Britney'));
        $list->add(new User('Chrissy'));
        return $list;
    }),
];
```

This will now have the expected outcome, since the action is guaranteed to run after all other extensions.

## Wires

Wires are a convenience built on top of actions that can be used for better code co-location and separation of
concerns across your modules.

Consider the example from the previous section, split into 2 modules:

```php
// module1.php
$module1 = [
    'users' => instance(List::class),
];
```

```php
// module2.php
$module2 = [
    extend('list', bind(function(List $list) {
        $list->add(new User('Charlie'));
    }),
];
```

The second module extends the `list` service from the first module to add a new user. This burdens the second module
with knowing _how_ to add a new user. Specifically, it must know that a `list` service exists, that it is a `List`
object, and that new users need to added via the `add()` method.

Imagine if we rename `List::add()` to `Registry::register()`, or change some service IDs. We'd have to update every
extension for the `users` service to use the new methods or new service IDs.

Ideally, these details are encapsulated in the first module. And wires are a way to achieve that!

---

First, the "burdened" module must provide a "wire", which is similar to an action:

```php
// module1.php
$module1 = [
    'users' => instance(List::class),
    'add_user' => wire('users', function(List $users, User $user) {
        $users->add($user);
    },
];
```

Under the hood, this will create an action for the `users` service that runs the wire function for every connected
service. The wire function takes the extended service and the connect service as arguments.

_Tip: If you need to use other services in your wire function, you can pass a bound function._

We can connect other services to the wire by calling the `wire()` method on wrapped services.

```php
// module2.php
$module2 = [
    'albert' => factory(fn() => new User('Alberg'))->wire('add_user'),
    'bobby' => factory(fn() => new User('Bobby'))->wire('add_user'),
];
```

Now, when `users` is being created by the DI container, the wire action will run its function twice; once for `albert`
and once for `bobby`.

This allows the second module to register users without knowing the details of the first module. We can now change
the internals of the first module, and as long as we also update the wire, other modules should be unaffected.

# Run Callbacks

Run callbacks are functions that are provided by a module that need to be invoked when the application "runs". You
may recall that when you create an app, you can call `$app->run()`. This is when module run callbacks are invoked.

There is no limit on how many run callbacks a module can provide. They will all be run in sequence, in the same order
provided by the module.

The easiest way to add a run callback to a module is to wrap a service definition in `run()`. This service can usually
be left anonymous.

```php
$module = [
    run(function(ContainerInterface $c) {
        echo "App is running!\n";
    }),
];
```

And of course, we can use bind here too:

```php
$module = [
    'version' => value('1.0'),

    run(bind(
        function($version) {
            echo "App v{$version}\n";
        },
        ['version']
    ),
];
```

Run callbacks can also be attached to wrapped services using the `runs()` method.

If you provide a bound function, the service's own value will be added as the last dependency:

```php
$module = [
    'server' => instance(Server::class)->runs(bind(function($server) {
        $server->start();
    })),
];
```

Alternatively, you can use `->then(...)` as a shorthand for `->runs(bind(...))`.

```php
$module = [
    'server' => instance(Server::class)->then(function($server) {
        $server->start();
    }),
];
```

If your run callback is already declared as a service, you can use [`invoke()`](#invoke()) to run it:

```php
$module = [
    'init' => callback(fn() => printf('Initializing...')),
    run(invoke('init')),
];
```

It even works on service-attached run callbacks:

```php
$module = [
    'init' => callback(fn(Server $s) => $s->start()),
    'server' => instance(Service::class)->run(invoke('init'))
];
```

_Note: `invoke()` cannot be used with `$service->then()`._

# Scoping

Scoping is the act of taking a module and prefixing all of their services to ensure that none of its service IDs
conflict with those from other modules.

It's not enough to simply prefix each service's ID with a string. Doing so would break the services that depend on the
original ID. To properly scope a module, we must also prefix every service's dependencies. For this reason, scoping
only works on **wrapped services**.

----

To scope a module, use the `scope()` function.

```php
$module = scope('greeter.', [
    'name' => value('Michael Scott'),
    'message' => template('Hello %s', ['name']),
]);
```

The above becomes equivalent to:

```php
$modules = [
    'greeter.name' => value('Michael Scott'),
    'greeter.message' => template('Hello %s', ['greeter.name'])
];
```

_Note: the use of `.` as a prefix separator is not enforced. You can use any prefix you like._

**Excluding IDs**

You may wish to exclude some ideas from scoping. For instance, when a service depends on a service from another module.

To exclude an ID, prefix it with the `@` symbol.

```php
$module1 = scope('greeter.', [
    'message' => template('Hello %s', ['@name']),
]);

$module2 = [
    'name' => value('Michael Scott'),
];
```

This allows us to scope modules while maintaining references to services from other modules. As an added bonus, the
service ID now explicitly indicates, by virtue of the `@` prefix, that it refers to an external service.

# Advanced Setup

## Decorating modules

Since modules are just `iterable` values of services, you can decorate them easily with a generator function.

For example, let's say you want to inject run callbacks for every service with a `!` ID prefix:

```php
function autorun(iterable $module) {
    foreach ($module as $id => $service) {
        if (is_string($id) && $id[0] === '!') {
            $id = substr($id, 1);
            yield run(invoke($id));
        }

        yield $id => $service;
    }
}
```

## Custom container

If you need to use a specific DI container implementation, you can provide a factory function to the `App` class:

```php
$app = new App([], fn($factories, $extensions) => /*...*/);
```

The factory function will recieve the PSR-11 service factories and extensions, as associative arrays where the array
keys are the service/extension IDs.

If your container does not support extensions, you can merge the extensions into your factories using the
`mergeExtensions()` function:

```php
$app = new App([], function ($factories, $extensions) {
    $merged = mergeExtensions($factories, $extensions);
    return new MyContainer($merged);
});
```

You don't need to use the `App` class. This class is merely a convenient wrapper around the `Compiler`. If you
require more control over how your modules are handled, you can interface with the compiler directly:

```php
$compiler = new Compiler($modules);
$compiler->addModules($moreModules);
$compiler->addModule($anotherOne);
```

The compiler will incrementally update its internal data after each added module. You can extract the compiled
factories, extensions, and the callback using the following methods:

```php
$factories = $compiler->getFactories();
$extensions = $compiler->getExtensions();
$callback = $compiler->getCallback();
```

The factories and extensions are typically given to a DI container. The callback will need to be run by you at an
appropriate point in time in your application's execution.

For convenience, the compiler also provides a `runCallback()` method that takes a container and runs the callback. You
can use this method as an alternative to getting the callback from the compiler and calling it yourself:

```php
$container = new MyContainer(
    $compiler->getFactories(),
    $compiler->getExtensions(),
);

$compiler->runCallback($container);
```

## Compatibility with Dhii Modules

**Note**: _I intend to include a compatibility layer for Dhii modules, but due to Composer dependency issues related to
the Dhii module package and its own dependencies, this is currently not possible. Conversion must be done manually. You
can track the progress of this issue [here](https://github.com/Dhii/module-interface/issues/23)._

The Dhii module system requires modules to be instances of [`Dhii\Module\ModuleInterface`](https://github.com/Dhii/module-interface).
In this system, modules have a `setup()` method that returns a `ServiceProviderInterface`, from the (now abandoned)
[service provider spec](https://github.com/Dhii/module-interface), and a `run()` method.

Converting an iterable module to a Dhii module is actually suprisingly simple.

First, create a compiler for a single module and obtain the compiled data:

```php
$compiler = new Compiler([$module]);
$factories = $compiler->getFactories();
$extensions = $compiler->getExtensions();
$callback = $compiler->getCallback();
```

Next, create a generic Dhii module that takes this data and exposes it through its interface methods:

```php
$dhiiModule = new HybridModuleThing($factories, $extensions, $callback);

class HybridModuleThing implements ModuleInterface, ServiceProviderInterface
{
    public function __construct(
        protected array $factories,
        protected array $extensions,
        protected callable $callback
    ) {}

    public function setup(): ServiceProviderInterface
    {
        return $this;
    }

    public function getFactories(): array
    {
        return $this->factories;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function run(ContainerInterface $c): void
    {
        ($this->callback)($c);
    }
}
```

**Tip**: You can make your Dhii module implement `ServiceProviderInterface` and then `return $this` from `setup()`.

# License

This project is licensed under the [MIT License](./LICENSE).

Copyright © 2023 Miguel Muscat
