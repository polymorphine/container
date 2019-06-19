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
use Polymorphine\Container\TrackingRecordContainer;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class ContainerSetup
{
    private $records;
    private $container;

    /**
     * Optional secondary container accessed with identifier $prefix.
     *
     * @param ContainerInterface $container
     * @param string             $prefix
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(ContainerInterface $container = null, string $prefix = '.')
    {
        $this->records = $container
            ? new CombinedRecordCollection([], $container, $prefix)
            : new RecordCollection();
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

        return $this->container = $tracking
            ? new TrackingRecordContainer($this->records)
            : new RecordContainer($this->records);
    }

    /**
     * Returns RecordSetup object able to configure Container's
     * Record slot for given name id.
     *
     * @param string $name
     *
     * @return RecordSetup
     */
    public function entry(string $name): RecordSetup
    {
        return new RecordSetup($name, $this->records);
    }

    /**
     * Stores Records instantiated directly in container.
     *
     * @param Record[] $records Flat associative array of Record instances
     */
    public function records(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->records->add($id, $record);
        }
    }
}
