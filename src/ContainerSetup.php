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


class ContainerSetup
{
    private $records;
    private $container;

    /**
     * @param RecordCollection $records (optional)
     */
    public function __construct(RecordCollection $records = null)
    {
        $this->records = $records ?? new RecordCollection();
    }

    /**
     * Returns Container instance with provided records.
     *
     * Adding new entries to container is possible only through
     * this setup instance, but early access to container might be
     * necessary. Cannot lock instance right after exposing it - for
     * example routing is both stored within container and built
     * with references to its records.
     *
     * Strict immutability cannot be ensured, because side-effects
     * can change subsequent call outcomes for stored identifiers.
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
            ? new TrackingContainer($this->records)
            : new Container($this->records);
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
