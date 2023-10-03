<?php

use Psr\Container\ContainerInterface;

return function (ContainerInterface $c) {
    return $c->get('foo') + $c->get('bar');
};
