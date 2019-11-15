<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container;

use Psr\Container\ContainerInterface;


class CompositeContainer implements ContainerInterface
{
    public const SEPARATOR = '.';

    private $records;
    private $containers;

    /**
     * @param Records              $records
     * @param ContainerInterface[] $containers
     */
    public function __construct(Records $records, array $containers)
    {
        $this->records    = $records;
        $this->containers = $containers;
    }

    public function get($id)
    {
        [$containerId, $itemId] = $this->splitId($id);
        if (isset($this->containers[$containerId])) {
            return $itemId ? $this->containers[$containerId]->get($itemId) : $this->containers[$containerId];
        }

        return $this->records->get($id, $this);
    }

    public function has($id)
    {
        [$containerId, $itemId] = $this->splitId($id);
        if (isset($this->containers[$containerId])) {
            return $itemId ? $this->containers[$containerId]->has($itemId) : true;
        }

        return $this->records->has($id);
    }

    private function splitId(string $id): array
    {
        return explode(static::SEPARATOR, $id, 2) + [false, null];
    }
}