<?php

declare(strict_types=1);

namespace Mecha\Modules\Stubs;

use Psr\Container\ContainerInterface;

class TestContainer implements ContainerInterface
{
    protected array $data;

    /** @param array<string,mixed> $data */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /** @return mixed */
    public function get($id)
    {
        return $this->data[$id] ?? null;
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->data);
    }
}
