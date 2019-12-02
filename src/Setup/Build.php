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

use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;
use Polymorphine\Container\Records;
use Polymorphine\Container\Setup\Entry\Wrapper;
use Psr\Container\ContainerInterface;


abstract class Build implements Collection
{
    protected $records;
    protected $containers;

    public function __construct(array $records = [], array $containers = [])
    {
        $this->records    = $records;
        $this->containers = $containers;
    }

    public function container(): ContainerInterface
    {
        return $this->containers
            ? new CompositeContainer($this->records(), $this->containers)
            : new RecordContainer($this->records());
    }

    public function addRecord(string $id, Records\Record $record): void
    {
        $this->records[$id] = $record;
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->containers[$id] = $container;
    }

    public function replaceRecord(string $id, Records\Record $record): void
    {
        $this->records[$id] = $record;
    }

    public function replaceContainer(string $id, ContainerInterface $container): void
    {
        $this->containers[$id] = $container;
    }

    public function decorator(string $id): Wrapper
    {
        if (!isset($this->records[$id])) {
            throw Exception\IntegrityConstraintException::undefined($id);
        }

        return new Entry\Wrapper($id, $this->records[$id], new Entry\ReplaceEntry($id, $this));
    }

    abstract protected function records(): Records;
}
