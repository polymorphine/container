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
use Polymorphine\Container\ConfigContainer;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class ContainerSetup
{
    private $records;
    private $container;

    /**
     * Records stored under keys starting with configuration
     * $prefix will not be accessible, because container will
     * assume configuration entry.
     *
     * @param Record[] $records Flat associative array of Record instances
     * @param array    $config  Associative (multidimensional) array of configuration values
     * @param string   $prefix  Container entry id prefix used to identify config container values
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $records = [], array $config = [], string $prefix = '.')
    {
        $this->records = $config
            ? new CombinedRecordCollection($records, new ConfigContainer($config), $prefix)
            : new RecordCollection($records);
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
}
