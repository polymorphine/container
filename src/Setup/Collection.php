<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup;

use Polymorphine\Container\Records;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class Collection
{
    protected const SEPARATOR = CompositeContainer::SEPARATOR;

    protected $records;
    protected $containers;

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     */
    public function __construct(array $records = [], array $containers = [])
    {
        $this->records    = $records;
        $this->containers = $containers;
    }

    public function container(): ContainerInterface
    {
        return $this->containers
            ? new CompositeContainer(new Records($this->records), $this->containers)
            : new RecordContainer(new Records($this->records));
    }

    public function add(string $id, Records\Record $record): void
    {
        if (isset($this->records[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $id));
        }

        $this->records[$id] = $record;
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        if (isset($this->containers[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` container', $id));
        }

        $this->containers[$id] = $container;
    }

    public function moveRecord(string $id): string
    {
        if (!isset($this->records[$id])) {
            throw Exception\RecordNotFoundException::cannotWrap($id);
        }

        $newId = $id . '.WRAP';
        while (isset($this->records[$newId])) {
            $newId .= '.WRAP';
        }

        $this->records[$newId] = $this->records[$id];
        unset($this->records[$id]);

        return $newId;
    }
}
