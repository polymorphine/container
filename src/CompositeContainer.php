<?php declare(strict_types=1);

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
use Psr\Container\NotFoundExceptionInterface;


/**
 * RecordContainer merged with additional sub-containers accessed
 * with separated prefix identifier.
 */
class CompositeContainer implements ContainerInterface
{
    public const SEPARATOR = '.';

    private Records $records;
    private array   $containers;

    /**
     * Container identifiers cannot contain separator and Records will
     * not be called when their identifier or its prefix is used by
     * existing container.
     *
     * @param Records              $records
     * @param ContainerInterface[] $containers Flat associative with string identifier prefix keys
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
            return $itemId ? $this->fromContainer($containerId, $itemId) : $this->containers[$containerId];
        }

        return $this->records->get($id, $this);
    }

    public function has($id): bool
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

    private function fromContainer(string $containerId, string $id)
    {
        try {
            return $this->containers[$containerId]->get($id);
        } catch (NotFoundExceptionInterface $e) {
            throw Exception\RecordNotFoundException::notFoundInSubContainer($containerId, $id, $e);
        }
    }
}
