<?php

declare(strict_types=1);

namespace Mecha\Modules;

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
