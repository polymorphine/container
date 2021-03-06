<?php declare(strict_types=1);

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
use Psr\Container\ContainerInterface;


class Build implements Collection
{
    protected array $records;
    protected array $containers;

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     */
    public function __construct(array $records = [], array $containers = [])
    {
        $this->records    = $records;
        $this->containers = $containers;
    }

    /**
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->containers
            ? new CompositeContainer($this->records(), $this->containers)
            : new RecordContainer($this->records());
    }

    public function has(string $id): bool
    {
        return isset($this->records[$id]) || isset($this->containers[$id]);
    }

    public function setRecord(string $id, Records\Record $record): void
    {
        $this->records[$id] = $record;
    }

    public function setContainer(string $id, ContainerInterface $container): void
    {
        $this->containers[$id] = $container;
    }

    protected function records(): Records
    {
        return new Records($this->records);
    }
}
