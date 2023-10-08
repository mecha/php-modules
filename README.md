# PHP Modules

A system for assembling a PHP application from a series of reusable modules.

⚠ **This package is still a work in progress**.

# Table of Contents

- [Motivation](#motivation)
- [Quick Start](#quick-start)
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

The need to split an application into "slices", or modules, that facilitate the wiring of application components, allow
for modular assembly of application features, and provide better separation between application surfaces.

This module system is primarly a revision of the [Dhii module spec](https://github.com/Dhii/module-interface) (which I
co-authored) that provides an all-in-one solution for building modular applications, rather than a spec for module
interoperability. While the goal of this package is to provide everything you may need, most features are optional.
The system is built to work with the bare-minimum: arrays of PSR-11 service definitions.

# Quick Start

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

By default, the `App` will use the bundled DI container implementation. If you need to use your own DI container, refer
to the [Advanced Setup](#advanced-setup) section.

# Modules

A modules is any **iterable** value (arrays, iterators, and generators) that provides PSR-11 service definitions, which
are functions that take a DI container to create and return some value.

**Array module example:**

```php
$module = [
    'greeter' => function(ContainerInterface $c) {
        return new Greeter($c->get('message'));
    },
    'message' => fn() => 'Hello world',
];
```

**Generator module example:**

```php
function module() {
    yield 'greeter' => function(ContainerInterface $c) {
        return new Greeter($c->get('message'));
    };

    yield 'message' => fn() => 'Hello world';
];
```

**Note**: When a module is added to an application, its service definitions will be registered with a DI container. The
order of your modules may affect how same-key conflicts are settled. If you wish to avoid all ID conflicts, consider
[scoping](#scoping) your modules.

# Services

Modules are expected to provide PSR-11 service definitions, but that doesn't mean we should write them manually.

This package provides a number of helper functions that make service definitions easier to write, more readable, and
also provide access to the majority of the module system's features.

## Binding

Consider the below service definition:

```php
function (ContainerInterface $c) {
    if ($c->get('debug_enabled')) {
        return new DebugThing($c->get('some_value'));
    } else {
        return new NormalThing($c->get('other_value'));
    }
}
```

This service depends on the existence of 3 other services in the DI container: `debug_enabled`, `some_value`, and
`other_value`. These dependencies are known only to the code inside the definition function, as calls to the 
container's `get()` method.

Wouldn't it be easier if we could write our service definition as a "normal" function?

```php
function (bool $debugEnabled, $someValue, $otherValue) {
    if ($debugEnabled) {
        return new DebugThing($someValue);
    } else {
        return new NormalThing($otherValue);
    }
}
```

While the above version is much less verbose and easier to read, it is not a valid PSR-11 service definition.

This is where the `bind()` function comes in.

This function takes our "normal" function and a list of service IDs, and returns a valid PSR-11 service definition:

```php
bind(
    function (bool $debugEnabled, $someValue, $otherValue) { ... },
    ['debug_enabled', 'some_value', 'other_value']
);
```

More specifically, the `bind()` function drops the 1st argument (the container), keeps any other arguments passed to
the service, and adds the resolved values of the given dependencies at the end of the argument list. Dependencies are
always passed _after_ invocation arguments. This will be important later.

Since the result of `bind()` is a normal PSR-11 service definition, the result can be used with many of the other
service functions provided by the module system.

## Wrapping

Some of the module system's features, such as [extensions](#extensions), [actions](#actions), and [wires](#wires),
require service definitions to provide additional information. This is done using
[invocable objects](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke), that act like functions but
can also have properties and methods.

This wrapping is done using the `service()` function, which simply takes a PSR-11 service definition:

```php
service(function(ContainerInterface $c) { ... });
```

It can also accept the result of `bind()`:

```php
service(bind(fn(int $timeout) => $timeout + 1, ['timeout']);
```

You won't usually need to call this function directly. Instead, you'll most likely be using the other helper
functions, which use `service()` under the hood.

### `factory()`

This is the simplest wrapper. It creates a wrapped, bound definition for any function you provide:

```php
use function Mecha\Modules\factory;

$module = [
    'message' => fn() => 'Hello world',
    'greeter' => factory(fn(string $msg) => new Greeter($msg), ['message']),
];
```

### `instance()`

This is a more specialized form of [`factory()`](#factory()) that facilitates constructing instances, with the dependencies as
constructor arguments.

```php
use function Mecha\Modules\instance;

$module = [
    'message' => fn() => 'Hello world',
    'greeter' => instance(Greeter::class, ['message']),
];
```

### `callback()`

A specialized form of [`factory()`](#factory()) that is equivalent to giving `factory()` a function that returns another
function.

The result is a wrapped, bound service definition that returns the function.

```php
use function Mecha\Modules\factory;
use function Mecha\Modules\callback;

factory(
    function($dep1, $dep2) {
        return fn($arg1, $arg2) => /*...*/;
    },
    ['dep1', 'dep2']
);

// Equivalent to:

callback(
    fn($arg1, $arg2, $dep1, $dep2) => /*...*/,
    ['dep1', 'dep2']
);
```

Example:

```php
use function Mecha\Modules\callback;

$module = [
    'format' => fn() => 'Hello %s',
    'greeter' => callback(
        fn($name, $format) => sprintf($format, $arg),
        ['format']
    ),
];
```

The function given to `callback()` will recieve invocation arguments first, followed by the resolved dependencies, if
the service has any.

For instance, in the above example, `greeter` takes 2 arguments, but one is bound to the `format` dependency. This means
that the resulting function only accepts 1 argument:

```php
$greeter = $container->get('greeter');
greeter('John'); // Output: "Hello John"
```

### `template()`

A specialized form of [`factory()`](#factory()) that use a printf-style template to create a string value with its dependencies:

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
    'name' => value('Pumba'),
];
```

**Note**: `value()` is similar to `service(fn() => 'Hello %s')`, with the subtle difference that the value is not
created inside a function. This makes `value()` more "eager" than using a function. If you need the value to be lazily
created at runtime, use an arrow function or [`factory()`](#factory()) instead.

### `alias()`

Creates a wrapped, bound service definition that simply resolves to its single dependency; great for creating aliases
to other services.

```php
use function Mecha\Modules\alias;

$module = [
    'person' => fn() => 'Thomas Anderson',
    'chosen_one' => alias('person'),
];
```

### `collect()`

Creates a wrapped, bound service definition that simply returns its resolved dependencies in an array:

```php
use function Mecha\Modules\collect;
use function Mecha\Modules\value;

$module = [
    'admin' => value('GLaDOS'),
    'tester' => value('Chell'),
    'assistant' => value('Wheatley'),

    'everyone' => collect(['admin', 'tester', 'assistant']),
];
```

### `env()`

Creates a wrapped service definition that resolves to the value of an environment variable.

```php
use function Mecha\Modules\env;

$module = [
    'editor' => env('EDITOR'),
];
```

### `constValue()`

Creates a wrapped service definition that resolves to the value of a defined constant.

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

Creates a wrapped service definition that resolves to the value of a global variable.

```php
use function Mecha\Modules\globalVar;

global $user;

$module = [
    'user' => globalVar('user'),
];
```

**Note**: The created service definition will capture the value of the global variable at the time of invocation.
If you are a caching DI container, such as the provided imlementation, then re-assignment of the global variable won't
be reflected by the service. Direct mutations, however, should still be reflected.

```php
global $foo;
$foo = 1;

$app = new App([
    $module = [
        'foo' => globalVar('foo'),
    ],
]);

$app->run();
$app->get('foo'); // => 1
$foo = 2;
$app->get('foo'); // => Still 1
```

### `load()`

Creates a wrapped service definition that loads a service definition that is returned by a PHP file. There are 2 ways
to use this function:

1. If no dependencies are given, the function in the specified file will recieve the DI container:

```php
// module.php
$module = [
    'foo' => load('my-service.php'),
];

// my-service.php
return function(ContainerInterface $c) {
    /*...*/
};
```

2. If dependencies are given, the function in the specified file is expected to be in bound-form:

```php
// module.php
$module = [
    'foo' => load('my-service.php', ['dep1', 'dep2']),
];

// my-service.php
return function ($dep1, $dep2) {
    /*...*/
};
```

### `invoke()`

Creates a bound, **but not wrapped**, service definition that gets another service by its ID, calls its resolved value
with the dependencies as arguments, and resolves to its return value.

```php
$module = [
    'name' => value('Luke'),
    'msg_fn' => callback(fn($name) => "Hello, I am %s."),
    'the_msg' => invoke('msg_fn', ['name']),
];
```

The above is equivalent to:

```php
$module = [
    'name' => fn() => 'Luke',
    'msg_fn' => fn() => fn($name) => "Hello, I am %s.",
    'the_msg' => function (ContainerInterface $c) {
        $fn = $c->get('msg_fn');
        $arg = $c->get('name');
        return $fn($arg);
    },
];
```

## Extensions

Extensions are service definitions that modify the resolved value of another service. This works across modules as well,
which makes extensions a great way to integrate modules together.

Extension definitions take the DI container as argument - just like service definitions - but also accept a second
`$previous` argument which holds the previous value of the service. The return value of the extension definition will
become the new value for that service.

```php
function (ContainerInterface $c, $prev) {
    // ...
    return $new;
}
```

Bound functions can also be used for extensions:

```php
bind(
    function($prev, $dep) {
        /* ... */
        return $new;
    },
    ['dep']
);
```

A service can have multiple extensions, which get called in sequence. Each extension beyond the first one will recieve
the previous extension's return value as the 2nd argument.

There are 2 ways to create extensions:

**1. Using the `extend()` function:**

```php
$module = [
    'footer' => value('My ugly blog'),

    extend('footer', function(ContainerInterface $c, $footer) {
        return "$footer | Copyright 2023";
    }),

    extend('footer', function(ContainerInterface $c, $footer) {
        return "$footer | Site is under maintenance.";
    }),
];

$app = new App([$module]);
$app->run();
$app->get('footer'); // => "My ugly blog | Copyright 2023 | Site is under maintenance."
```

Extensions declared in this way are typically anonymous. But you can include a key if you'd like!

**2. Using the `extends()` method on wrapped services:**

```php
$module = [
    'list' => instance(AnimalList::class),
    'item' => value('Pumba')->extends('list', function ($c, $list) {
        $list->add($c->get('item'));
        return $list;
    }),
];
```

If you use `bind()` for your extension, the module system will automatically add the wrapped service's value as a
dependency to the bound extension, which adds the value of the service at the end of the argument list:

```php
$module = [
    'list' => instance(AnimalList::class),
    'item' => value('Pumba')->extends('list', bind(function ($list, $self) {
        // $self is the value of 'item'
        $list->add($self);
        return $list;
    }),
];
```

## Actions

Actions are a special type of [extension](#extensions) that do not perform any actual extending.

Consider the below scenario:

We have a module that provides a list of users, and extends the list so that when it gets created, each user in the
list gets their preferences loaded. Note how the extension does not actually extend the list.

```php
$module = [
    'users' => instance(List::class),

    extend('users', bind(function(List $users) {
        foreach ($users as $user) {
            $user->loadPreferences();
        }

        return $users;
    }),
```

This is a common pattern. While we can load user preferences using a [run callback](#run-callbacks), this approach
ensures that user preferences are only loaded if the `users` service is used by another service, rather than everytime
the application runs.

Let's now add a second extension to add some users to the list:

```php
$module = [
    /* ... */
    extend('users', bind(function(List $users) {
        $users->add(new User('Abigail'));
        $users->add(new User('Britney'));
        $users->add(new User('Chrissy'));
        return $users;
    }),
];
```

This, unfortunately, won't have the expected outcome. Extensions are invoked in the same order they are provided in,
which means that the first extension will recieve an empty list. The second list will then add the users, but by that
point it would be too late.

To fix this, we'll need to reorder the extensions in this module. However, this isn't always possible, such as when the
extensions are in different modules. We can try and carefully order our modules before adding them to our application,
but this assumes that there exists some order of modules that satisfies _all_ extensions.

---

This is where actions come in.

Actions are simply extensions whose return value is ignored and are run _after_ regular extensions. This guarantees
that actions always recieve the final value of the extended service.

They are called "actions" because they are most commonly used to run pieces of code whenever another service is fetched
from the DI container.

Just like extensions, there are 2 ways to create an action:

**1. Using the `action()` function**

```php
use function Mecha\Modules\action;
use function Mecha\Modules\bind;
use function Mecha\Modules\extend;
use function Mecha\Modules\instance;

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

**2. Using the `on()` method on wrapped services:**

And just like with extensions, bound functions will recieve the value of the service that the action is attached to, as
the last argument:

```php
use function Mecha\Modules\bind;
use function Mecha\Modules\extend;
use function Mecha\Modules\instance;

$module = [
    'db' => instance(MyDb::class),

    'migrations' =>
        load('migrations.php')
        ->on('db', bind(function(MyDb $db, Migrator $self) {
            $self->runIfNeeded($db);
        })),
];
```

## Wires

Wires are a convenience built on top of [actions](#actions) that help improve code co-location and separation of
concerns across your modules.

Consider the below modules. The first module provides a list of users:

```php
$module1 = [
    'users' => instance(List::class),
];
```

The second module adds a user to the list using an [extension](#extensions):

```php
$module2 = [
    extend('list', bind(function(List $list) {
        $list->add(new User('Charlie'));
    }),
];
```

This burdens the second module with knowing _how_ to add a new user. Specifically, it must know that a `list` service 
exists, that it is a `List` object, and that new users need to added via the `add()` method.

If we were to change the `users` service in the first module from a `List` to an `array`, we'd need to also update the
extension in the second module to work with arrays. If we had more extensions in other modules, they'd need to be
updated as well.

Ideally, such changes are only required in the first module; the one that provides the `users` service. Wires are a way
to do exactly that!

---

Wires are a way to run a function for a "target" service with other "connected" services. They are created using the
`wire()` function.

For example, the below creates a wire for the `users` service:

```php
$module1 = [
    'users' => instance(List::class),
    'add_user' => wire('users', function(List $users, User $user) {
        $users->add($user);
    },
];
```

The `wire()` function recieves the value of the target service (e.g. the user list) and a connected service (e.g. a
single user). Under the hood, this will create an action for the `users` service that runs the wire's function for every connected
service.

Services can then be connected to the wire by calling the `wire()` method on wrapped services.

```php
$module2 = [
    'albert' => value(new User('Albert'))->wire('add_user'),
    'bobby' => value(new User('Bobby'))->wire('add_user'),
];
```

Now, when the `users` service is being created by the DI container, the wire action will run its function twice; once
for `albert` and once for `bobby`.

This allows the second module to extend the `users` service in the first module without needing to know _how_ to do
the extending itself. All it needs to know is the ID of the wire; all other details are contained in the first module.

**Using `bind()` with wires**:

While the `wire()` function's 2nd argument does not accept service definitions, you can still provide a bound function.
This is especially useful if your wire function needs to have some dependencies:

```php
$module1 = [
    wire('users', bind(function (List $users, User $user, $dep) {
        // ...
    }, ['dep'])),
];
```

# Run Callbacks

Run callbacks are functions that are provided by a module that need to be invoked when the application "runs". You
may recall that when you create an app, you can call `$app->run()`. This is when module run callbacks are invoked.

A module can provide any number of run callbacks. The module system will run them all in the same order they are 
provided by the module. There are 2 ways to provide run callbacks:

**1. Using the `run()` function**:

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

Like extensions, run actions are usually left anonymous (no ID).

**2. Using the `runs()` method on wrapped services**:

```php
$module = [
    'server' =>
        instance(Service::class)
        ->runs(fn($c) => $c->get('server')->start()),
];
```

Just like [extensions](#extensions) and [actions](#actions), bound functions will also recieve the service's own value
as the last dependency:

```php
$module = [
    'server' =>
        instance(Server::class)
        ->runs(bind(fn(Server $server) => $server->start())),
];
```

Alternatively, you can use `->then(...)` as a shorthand for `->runs(bind(...))`.

```php
$module = [
    'server' =>
        instance(Server::class)
        ->then(fn(Server $server) => $server->start()),
];
```

----

**Combining `run()` and `invoke()`**:

Consider this scenario: we have a module that provides a [`callback()`](#callback()) service that is fetched and
invoked as a run callback.

```php
$module = [
    'server' => instance(Server::class),
    'init' => callback(fn(Server $s) => $s->start(), ['server']),

    run(function(ContainerInterface $c) {
        $init = $c->get('init');
        $init();
    }),
];
```

This can be simplified by using the [`invoke()`](#invoke()) function:

```php
$module = [
    'server' => instance(Server::class),
    'init' => callback(fn(Server $s) => $s->start(), ['server']),

    run(invoke('init'))
];
```

It even works on service-attached run callbacks:

```php
$module = [
    'server' => instance(Server::class)->runs(invoke('init')),
    'init' => callback(fn(Server $s) => $s->start(), ['server']),
];
```

_Note: `invoke()` cannot be used with `$service->then()`._

## Generator Return Callback

If your module is a generator function, it may optionally return a service definition that will be treated by the
module system as a run callback.

```php
function my_module() {
    yield 'server' => instance(Server::class);

    return function(ContainerInterface $c) {
        $c->get('server')->start();
    };
}
```

You can use [`bind()`](#binding) here too:

```php
function my_module() {
    yield 'server' => instance(Server::class);

    return bind(function(Server $server) {
        $server->start();
    }, ['server']);
}
```

Both of the above examples have the same effect as having an anonymous `run()` service.

# Scoping

Scoping is the act of taking a module and prefixing all of its services to ensure that none of the service IDs conflict
with those from other modules.

It's not enough to simply prefix each service's ID with a string. Doing so would break the services that depend on the
original unprefixed ID. To properly scope a module, we must also prefix every service's dependencies. For this reason,
scoping only works on [wrapped services](#wrapping).

----

To scope a module, use the `scope()` function and give it the prefix string:

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

Notice how the dependency for `greeter.message` was also prefixed.

## Excluding IDs

You may wish to exclude some service IDs from scoping. A common case for this is when a module depends on a service
from another module.

To exclude an ID, prefix it with the `@` symbol.

```php
$module1 = scope('greeter.', [
    'message' => template('Hello %s', ['@name']),
]);

$module2 = [
    'name' => value('Michael Scott'),
];
```

When the first module is scoped, the `@name` dependency will be simply changed to `name`, which refers to the service
from the second module.

As an added bonus, the `@` symbol makes it clear to the reader that an ID refers to a service in anothor module.

## Scoping multiple modules

A `scopeAssoc()` function is also provided to facilitate the scoping of multiple modules at once. It takes an
associative array of modules as argument, and uses the array keys as the prefix strings:

```php
scopeAssoc([
    'greeter/' => $greeterModule,
    'config/' => $configModule,
    'db/' => $dbModule,
]);
```

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

By default, the module system will use the DI container implementation provided by this package. However, you can
change this to any PSR-11 container implementation by providing a factory function to the `App` class.

```php
$app = new App([], fn($factories, $extensions) => /*...*/);
```

The factory function will recieve the service factories and extensions as associative arrays, where the array keys are
the service and extension IDs, respectively.

If your container does not support extensions, you can merge the extensions into the factories using the
`mergeExtensions()` function:

```php
use Mecha\Modules\mergeExtensions;

$app = new App([], function ($factories, $extensions) {
    $merged = mergeExtensions($factories, $extensions);
    return new MyContainer($merged);
});
```

## Using the compiler directly

Using the `App` class it technically optional. This class is merely a convenient wrapper around the `Compiler`, which
is responsible for processing modules and compiling the PSR-11 container service definitions.

If you require more control over how your modules are handled, you can interface with the compiler directly:

```php
$compiler = new Compiler($modules);
$compiler->addModules($extraModules);
$compiler->addModule($oneMore);
```

The compiler will incrementally update its compiled data after each added module. You can extract the compiled
factories, extensions, and the merged [run callback](#run-callbacks) at any time using the following methods:

```php
$factories = $compiler->getFactories();
$extensions = $compiler->getExtensions();
$callback = $compiler->getCallback();
```

The factories and extensions are typically given to a DI container. The callback will need to be run by you at an
appropriate point in time in your application's execution.

For convenience, the compiler also provides a `runCallback()` method that takes a container and runs the callback. You
can use this as an alternative to getting the callback from the compiler just to call it immediately after:

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
