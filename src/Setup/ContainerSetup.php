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
use Polymorphine\Container\ConfigContainer;
use Polymorphine\Container\TrackingRecordContainer;
use Psr\Container\ContainerInterface as Container;
use Polymorphine\Container\Exception;


class ContainerSetup
{
    private $records;
    private $container;

    /**
     * @param RecordCollection $records
     */
    public function __construct(Records $records = null)
    {
        $this->records = $records ?? new RecordCollection();
    }

    /**
     * Provided Record entries will not be validated, make sure to avoid id conflicts
     * with secondary container prefix and type or nesting Record values.
     *
     * @param Record[]  $records   $records Associative (flat) array of Record entries
     * @param Container $container Secondary container instance
     * @param string    $prefix    Id prefix to access secondary container values
     *
     * @return ContainerSetup
     */
    public static function prebuilt(
        array $records,
        Container $container = null,
        string $prefix = '.'
    ): self {
        $records = $container
            ? new CombinedRecordCollection(new RecordCollection($records), $container, $prefix)
            : new RecordCollection($records);

        return new self($records);
    }

    /**
     * @param array  $config Associative (multidimensional) array of configuration values
     * @param string $prefix
     *
     * @return ContainerSetup
     */
    public static function withConfig(array $config, string $prefix = '.'): self
    {
        return new self(new CombinedRecordCollection(new RecordCollection([]), new ConfigContainer($config), $prefix));
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
     * @return Container
     */
    public function container(bool $tracking = false): Container
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
