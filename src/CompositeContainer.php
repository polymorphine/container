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
        return $this->records->has($id) ? $this->records->get($id, $this) : $this->fromContainers($id);
    }

    public function has($id)
    {
        return $this->records->has($id) || $this->inContainers($id);
    }

    private function fromContainers($id)
    {
        [$containerId, $itemId] = $this->splitId($id);
        if (!$containerId || !$itemId || !isset($this->containers[$containerId])) {
            throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $id));
        }

        return $this->containers[$containerId]->get($itemId);
    }

    private function inContainers(string $id): bool
    {
        [$containerId, $itemId] = $this->splitId($id);
        if (!$containerId || !$itemId) { return false; }

        return isset($this->containers[$containerId]) ? $this->containers[$containerId]->has($itemId) : false;
    }

    private function splitId(string $id): array
    {
        return $id[0] === static::SEPARATOR
            ? [static::SEPARATOR, ltrim($id, static::SEPARATOR)]
            : explode(static::SEPARATOR, $id, 2) + [false, null];
    }
}
