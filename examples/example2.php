<?php

use Mecha\Modular\CompositeModule;
use Mecha\Modular\Containers\CachingContainer;
use Mecha\Modular\Containers\ServiceProviderContainer;
use Mecha\Modular\Decorators\PrefixedModule;
use Mecha\Modular\Example\modules\GreetingModule;
use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Value;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$modules = [
    'admin_greet' => new PrefixedModule('admin_greet/', new GreetingModule()),
    'comp_greet' => new PrefixedModule('comp_greet/', new GreetingModule()),
    'main' => new class implements ModuleInterface {
        public function run(ContainerInterface $c)
        {
        }

        public function getFactories()
        {
            return [];
        }

        public function getExtensions()
        {
            return [
                'admin_greet/name' => new Value('Administrator'),
                'comp_greet/name' => new Value('Computer'),
            ];
        }
    },
];

// Create the app as a module that consists of other modules
$app = new CompositeModule($modules);

// $c will be the top-level container used by the app and its modules
$c = null;
// This function provides $c, and will be given to the inner container for delegation
$parentCb = function () use (&$c) {
    return $c;
};

// The inner DI container for the app
$spContainer = new ServiceProviderContainer($app, $parentCb);

// The top-level DI container
$c = new CachingContainer($spContainer);

// Run the app (which in turn runs its modules)
$app->run($c);
