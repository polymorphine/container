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


class Setup
{
    private $records;
    private $containers;
    private $container;

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     */
    public function __construct(array $records = [], array $containers = [])
    {
        $this->records    = new Records\RecordCollection($records);
        $this->containers = $containers;
    }

    /**
     * Returns Container instance with provided records.
     *
     * Adding new entries to container is still possible, but only
     * using this instance's entry() method.
     *
     * Strict immutability can be ensured only when this instance is
     * encapsulated and not passed to uncontrolled parts of application
     * (including container itself).
     *
     * @param bool $tracking Enables call stack tracking and detects
     *                       circular references
     *
     * @return ContainerInterface
     */
    public function container(bool $tracking = false): ContainerInterface
    {
        if ($this->container) { return $this->container; }

        $records = $tracking ? new Records\TrackedRecords($this->records) : $this->records;
        return $this->container = $this->containers
            ? new CompositeContainer($records, $this->containers)
            : new RecordContainer($records);
    }

    /**
     * Returns RecordSetup object able to configure Container's
     * Record slot for given name id.
     *
     * @param string $name
     *
     * @return Setup\RecordSetup
     */
    public function entry(string $name): Setup\RecordSetup
    {
        return new Setup\RecordSetup($name, $this->records);
    }

    /**
     * Stores Records instantiated directly in container.
     *
     * @param Records\Record[] $records Flat associative array of Record instances
     *
     * @throws Exception\InvalidIdException
     */
    public function records(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->records->add($id, $record);
        }
    }
}
